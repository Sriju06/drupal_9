<?php

namespace Drupal\node_auto_expire\Plugin\views\field;

use Drupal;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;
use Drupal\node_auto_expire\Form\NodeAutoExpireExpireForm;

/**
 * Class NodeAutoExpireExtendLink.
 *
 * @ViewsField("node_auto_expire_extend_link")
 */
class NodeAutoExpireExtendLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {

    $config = Drupal::config('node_auto_expire.settings');
    $drupal_state = Drupal::state();
    $nid = $row->nid;

    // Check if user has rights to extend.
    if (NodeAutoExpireExpireForm::access($nid)) {

      // Check if node is 'extendable' at this moment.
      $code = $drupal_state->get($config->get('node_auto_expire_node_type') . $row->_entity->getType());
      $expire = NodeAutoExpireExpireForm::nodeAutoExpireGetExpire($nid);
      $warn = $drupal_state->get($code . '_w', $config->get('node_auto_expire_warn'));
      $request_time = Drupal::time()->getRequestTime();

      if ($request_time > $expire - ($warn * 24 * 60 * 60)) {

        $text = !empty($this->options['text']) ? $this->options['text'] : t('Extend');

        return [
          '#title' => $text,
          '#type' => 'link',
          '#url' => Url::fromUri('internal:/' . 'node/' . $nid . '/expire', [
            'query' => Drupal::service('redirect.destination')
              ->getAsArray(),
          ]),
        ];

      }

    }

    return [];

  }

}
