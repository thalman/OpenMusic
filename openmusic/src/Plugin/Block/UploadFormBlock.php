<?php

namespace Drupal\openmusic\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Upload Form Block Block.
 *
 * @Block(
 *   id = "openmusic_score_upload_block",
 *   admin_label = @Translation("Upload Score"),
 *   category = @Translation("Forms"),
 * )
 */
class UploadFormBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $form = \Drupal::formBuilder()->getForm('Drupal\openmusic\Form\UploadForm');
        return $form;
    }
}
