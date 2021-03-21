<?php

namespace Drupal\openmusic\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Score Download block' Block.
 *
 * @Block(
 *   id = "openmusic_score_download_block",
 *   admin_label = @Translation("Download Score"),
 *   category = @Translation("openmusic"),
 * )
 */
class ScoreDownloadBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $current_path = \Drupal::service('path.current')->getPath();
        $score_id = "0";
        if (strncmp($current_path, "/openmusic/score/", 17) == 0) {
            $score_id = explode("/", $current_path)[3];
        }
        error_log("Score id: " . $score_id);
        return [
            "#theme" => "openmusicscoredownload",
            '#attributes' => [
                'class' => ['openmusic_score_download'],
                'id' => 'openmusic_score_download_block',
            ],
            '#scoreid'  => $score_id,
        ];
    }
}

?>
