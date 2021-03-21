<?php

namespace Drupal\openmusic\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Score pages block' Block.
 *
 * @Block(
 *   id = "openmusic_score_pages_block",
 *   admin_label = @Translation("Score Pages"),
 *   category = @Translation("openmusic"),
 * )
 */
class ScoreDetailBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        return [
            "#theme" => "openmusicscorepages",
            "#attributes" => [
                "class" => ["openmusic_score_pages"],
                "id" => "openmusic_score_pages_block",
            ],
            "#attached" => [
                "library" => [
                    "openmusic/openmusic-score"
                ],
            ],
        ];
    }
}

?>
