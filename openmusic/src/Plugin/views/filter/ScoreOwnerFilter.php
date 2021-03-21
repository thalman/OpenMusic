<?php

/**
 * @file
 * Definition of Drupal\openmusic\Plugin\views\filter\ScoreOwnerFilter.
 */

namespace Drupal\openmusic\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\user\Entity\User;
/**
 * Filters by logged in user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("openmusic_score_owner_filter")
 */
class ScoreOwnerFilter extends FilterPluginBase {

    /**
     * {@inheritdoc}
     */
    public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
        parent::init($view, $display, $options);
        $this->valueTitle = t('Score Owner Filter');
    }

    /**
     * add condition openmusicscore.owner = current_user
     */
    public function query() {
        $this->ensureMyTable();

        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->id();
        if ($user != 0) {
            $this->query->addWhere($this->options['group'], 'openmusicscore.owner', $user, "=");
        }
    }
}
