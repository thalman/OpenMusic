<?php

namespace Drupal\openmusic\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\file\Entity\File;

/**
 * Defines the OpenMusicScore entity.
 *
 * @ingroup openmusic
 *
 * @ContentEntityType(
 *   id = "openmusicscore",
 *   label = @Translation("OpenMusic Score"),
 *   base_table = "openmusicscore",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openmusic\ScoreListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/openmusicscore/{openmusicscore}",
 *     "collection" = "/openmusicscore/list",
 *   },
 * )
 */
class OpenMusicScore extends ContentEntityBase implements ContentEntityInterface {

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     *
     * When a new entity instance is added, set the user_id entity reference to
     * the current user as the creator of the instance.
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
        parent::preCreate($storage_controller, $values);
        // Default owner to current user.
        $values += array(
            "owner" => \Drupal::currentUser()->id(),
        );
    }

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

        // Standard field, used as unique if primary index.
        $fields["id"] = BaseFieldDefinition::create("integer")
            ->setLabel(t("ID"))
            ->setDescription(t("The ID of the OpenMusicScore entity."))
            ->setReadOnly(TRUE);

        $fields["uuid"] = BaseFieldDefinition::create("uuid")
            ->setLabel(t("uuid"))
            ->setDescription(t("The entity uuid."))
            ->setReadOnly(TRUE);

        $fields["owner"] = BaseFieldDefinition::create("entity_reference")
            ->setLabel(t("Owner"))
            ->setDescription(t("The user that uploaded the score."));

        $fields["uploaded"] = BaseFieldDefinition::create("changed")
            ->setLabel(t("Uploaded"))
            ->setDescription(t("When last version has been uploaded."));

        $fields["file"] = BaseFieldDefinition::create("entity_reference")
            ->setLabel(t("File"))
            ->setDescription(t("File reference to uploaded score."));

        // Standard field,
        $fields["composer"] = BaseFieldDefinition::create("string")
            ->setLabel(t("Composer"))
            ->setDescription(t("The Score Composer."))
            ->setSettings(array(
                "default_value" => "",
                "max_length" => 50,
                "text_processing" => 0))
            ->setDisplayOptions("view", array(
                "label" => "above",
                "type" => "string",
                "weight" => -6,
            ));

        // Standard field,
        $fields["title"] = BaseFieldDefinition::create("string")
            ->setLabel(t("Title"))
            ->setDescription(t("The Score Title."))
            ->setSettings(array(
                "default_value" => "",
                "max_length" => 255,
                "text_processing" => 0))
            ->setDisplayOptions("view", array(
                "label" => "above",
                "type" => "string",
                "weight" => -7,
            ));

        $fields["copyright"] = BaseFieldDefinition::create("string")
            ->setLabel(t("Copyright"))
            ->setDescription(t("The Score Copyright."))
            ->setSettings(array(
                "default_value" => "",
                "max_length" => 50,
                "text_processing" => 0));

        $fields["free"] = BaseFieldDefinition::create("boolean")
            ->setLabel(t("Free"))
            ->setDescription(t("True if copyright is public domain or CC"));

        // Standard field,
        $fields["number_of_instruments"] = BaseFieldDefinition::create("integer")
            ->setLabel(t("Number of instruments"))
            ->setDescription(t("Number of instruments."))
            ->setSettings(array("default_value" => 0));

        $fields["instruments"] = BaseFieldDefinition::create("string")
            ->setLabel(t("Instruments"))
            ->setDescription(t("Instruments used in the score."))
            ->setCardinality(50)
            ->setSettings(array(
                "default_value" => "",
                "max_length" => 50,
                "text_processing" => 0));

        $fields["bookmarks"] = BaseFieldDefinition::create("entity_reference")
            ->setLabel(t("Bookmarks"))
            ->setDescription(t("List of people who bookmarked this score."))
            ->setCardinality(\Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
            ->setSettings(array(
                "default_value" => "",
                "max_length" => 50,
                "text_processing" => 0));

        return $fields;
    }

    public function scoreCreate(&$file_id)
    {
        try {
            $file = File::load($file_id);
            $metadata = $this->scoreMeta($file);
            if (metadata == null) {
                throw new Exception("Failed to extract metadata from score");
            }

            $storage = "private://openmusic/" .  date("Ym") . "/";
            file_prepare_directory($storage, FILE_CREATE_DIRECTORY);
            $storage .= $this->uuid() . ".mscz";
            $file->setPermanent();
            $file->save();
            file_move($file, $storage, FILE_EXISTS_ERROR);
            $this->set("composer", $metadata["composer"]);
            $this->set("title", $metadata["title"]);
            $this->set("number_of_instruments", count($metadata["parts"]));
            $this->set("file", $file_id);
            $copyright = $this->scoreCopyright($file);
            $this->set("copyright", $copyright);
            $this->set("free", $this->isFree($copyright));
            $this->save();
        } catch (Exception $e) {
            error_log("Failed to add new score: ". $e->getMessage());
            drupal_set_message("Failed to add new score: ". $e->getMessage());
        }
    }

    public function scoreUpdate($file)
    {
    }

    public function scoreDelete()
    {
        /* check if it is mine */
        $fid = $this->get("file");
        $file = File::load($fid);
        $file->delete();
        $this->delete();
    }

    public function scoreMeta(&$file)
    {
        // get $file from this entity
        $path = drupal_realpath($file->getFileUri());
        $cmd = "/usr/bin/mscore-extract metadata \"" . $path . "\"";
        error_log("cmd: " . $cmd);
        $json = shell_exec("HOME=/opt/mscore-home " . $cmd);
        if ($json == null) {
            error_log("Error while parsing score metadata: score-meta failed");
            return null;
        }

        try {
            error_log("json: " . print_r($json,true));
            return json_decode($json, TRUE);
        } catch (Exception $e) {
            log_error("Error while parsing score metadata: " . $e->getMessage());
            return null;
        }
    }

    public function isFree($copyright)
    {
        if (preg_match("/Public +Domain/i", $copyright) === 1) {
            return true;
        }
        if (preg_match("/Creative +Commons/i", $copyright) === 1) {
            return true;
        }
        if (preg_match("/^CC *(0|BY|BY-SA|BY-NC|BY-NC-SA|BY-ND|BY-NC-ND)$/i", $copyright) === 1) {
            return true;
        }
        return false;
    }

    public function scoreCopyright(&$file)
    {
        $path = drupal_realpath($file->getFileUri());
        $cmd = "/usr/bin/mscore-extract copyright \"" . $path . "\"";
        error_log("cmd: " . $cmd);
        $copyright = exec($cmd);
        if ($copyright == null) {
            error_log("Error while extracting copyright");
            return "";
        }
        return trim($copyright);
    }

    public function hasBookmark($userid)
    {
        $bookmarks = $this->get("bookmarks")->getValue();
        foreach($bookmarks as $item) {
            if ($item["target_id"] == $userid) {
                return true;
            }
        }
        return false;
    }

    public function addBookmark($userid)
    {
        if ($this->hasBookmark($userid)) {
            return true;
        }

        try {
            $this->bookmarks[] = ['target_id' => $userid];
            $this->save();
            return true;
        } catch (Exception $e) {
            return false;

        }
    }

    public function deleteBookmark($userid)
    {
        $items = $this->get("bookmarks");
        $present = false;
        for ($i = $items->count() - 1; $i >= 0; $i--) {
            if ($items->get($i)->target_id == $userid) {
                $items->removeItem($i);
                $present = true;
            }
        }

        if ($present) {
            $this->save();
            return true;
        }
        return false;
    }
}
?>
