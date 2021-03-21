<?php

namespace Drupal\openmusic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\openmusic\Entity\OpenMusicScore;
use Drupal\file\Entity\File;

class ApiController extends ControllerBase {
    private function ScoreEntityToJson(&$entity)
    {
        $result = array();
        $result["id"] = $entity->id();
        $result["title"] = $entity->get("title")->getString();
        $result["composer"] = $entity->get("composer")->getString();
        $result["copyright"] = $entity->get("copyright")->getString();
        $result["number_of_instruments"] = $entity->get("number_of_instruments")->getValue()[0];
        return $result;
    }

    private function filenameFromTitle(&$entity)
    {
        $title = trim($entity->get("title")->getString());
        $title = preg_replace("/\s+/", "_", $title);
        $title = preg_replace("/[^a-zA-Z_]/", "", $title);
        if ($title == "") {
            $title = "score";
        }
        return $title;
    }

    private function userId()
    {
        try {
            return \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->id();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function List() {
        $query = \Drupal::entityQuery("openmusicscore");
        $ids = $query->execute();
        $controller = \Drupal::entityManager()->getStorage("openmusicscore");
        $result = array();
        $i = 0;
        foreach($scores = $controller->loadMultiple($ids) as $score) {
            $result[$i] = $this->ScoreEntityToJson($score);
            $i += 1;
        };
        return new JsonResponse($result);
    }

    public function getAllBookmarks() {
        $user = $this->userId();
        $database = \Drupal::database();
        $query = $database->select('openmusicscore__bookmarks', 'b');
        $query->fields('b', ['entity_id']);
        $query->condition('b.bookmarks_target_id', $user, '=');
        $result = array();
        foreach($query->execute() as $item) {
            $result[] = intval($item->entity_id);
        }
        return new JsonResponse($result);
    }

    public function getBookmark($score) {
        $entity = OpenMusicScore::load($score);
        if ($entity == null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        $user = $this->userId();
        //if ($user == 0) {
        //    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        //}

        return new JsonResponse($entity->hasBookmark($user));
    }

    public function setBookmark($score) {
        $entity = OpenMusicScore::load($score);
        if ($entity == null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $user = $this->userId();
        $retult = $entity->addBookmark($user);
        return new JsonResponse($result);
    }

    public function deleteBookmark($score) {
        $entity = OpenMusicScore::load($score);
        if ($entity == null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $user = $this->userId();
        $retult = $entity->deleteBookmark($user);
        return new JsonResponse($result);
    }

    public function Get($score, $what) {
        $entity = OpenMusicScore::load($score);
        if ($entity == null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        if ($what == "thumbnail") {
            $file_id = $entity->get("file")->getValue()[0]["target_id"];
            $file = File::load($file_id);
            $path = drupal_realpath($file->getFileUri());
            $cmd = "/usr/bin/mscore-extract thumbnail \"" . $path . "\"";
            $image = shell_exec($cmd);

            $response = new Response($image);
            $response->headers->set("Content-Type", "image/png");
            return $response;
        }
        if ($what == "pngs") {
            $file_id = $entity->get("file")->getValue()[0]["target_id"];
            $file = File::load($file_id);
            $path = drupal_realpath($file->getFileUri());
            $cmd = "/usr/bin/mscore-extract pngs \"" . $path . "\"";
            error_log("cmd " . $cmd);
            $response = new StreamedResponse();
            $response->headers->set("Content-Type", "application/json");
            $response->setCallback(function() use ($cmd) {
                    $h = popen($cmd, "r");
                    while(!feof($h)) {
                        $buffer = fread($h, 2048);
                        echo $buffer;
                    }
                    pclose($h);
                }
            );
            return $response;
        }
        if ($what == "pdf") {
            $file_id = $entity->get("file")->getValue()[0]["target_id"];
            $file = File::load($file_id);
            $path = drupal_realpath($file->getFileUri());
            $cmd = "/usr/bin/mscore-extract pdf \"" . $path . "\"";
            error_log("cmd " . $cmd);
            $response = new StreamedResponse();
            $response->headers->set("Content-Type", "application/pdf");
            $response->headers->set("Content-Disposition", "attachment; filename=\"" . $this->filenameFromTitle($entity) . ".pdf\"");
            $response->setCallback(function() use ($cmd) {
                    $h = popen($cmd, "r");
                    while(!feof($h)) {
                        $buffer = fread($h, 2048);
                        echo $buffer;
                    }
                    pclose($h);
                }
            );
            return $response;
        }
        if ($what == "mscz") {
            $file_id = $entity->get("file")->getValue()[0]["target_id"];
            $file = File::load($file_id);
            $path = drupal_realpath($file->getFileUri());
            $response = new StreamedResponse();
            $response->headers->set("Content-Type", "application/mscz");
            $response->headers->set("Content-Disposition", "attachment; filename=\"" . $this->filenameFromTitle($entity) . ".mscz\"");
            $response->setCallback(function() use ($path) {
                    $h = fopen($path, "r");
                    while(!feof($h)) {
                        $buffer = fread($h, 2048);
                        echo $buffer;
                    }
                    fclose($h);
                }
            );
            return $response;
        }
        return new JsonResponse($this->ScoreEntityToJson($entity));
    }
}

?>