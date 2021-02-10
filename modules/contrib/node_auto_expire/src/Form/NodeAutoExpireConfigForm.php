<?php

namespace Drupal\node_auto_expire\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Node Auto Expire settings for this site.
 */
class NodeAutoExpireConfigForm extends ConfigFormBase {

  /**
   * State Interface.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $state;

  /**
   * Config Entity Storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $nodeType;

  /**
   * NodeAutoExpireConfigForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State interface injection.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorage $node_type
   *   Config entity storage injection.
   */
  public function __construct(StateInterface $state, ConfigEntityStorage $node_type) {
    $this->state = $state;
    $this->nodeType = $node_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the services required to construct this class.
      $container->get('state'),
      $container->get('entity_type.manager')->getStorage('node_type')
    );
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return [
      'node_auto_expire.settings',
    ];
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'node_auto_expire_settings';
  }

  /**
   * Implements buildForm().
   *
   * @param array $form
   *   Comment about this variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Comment about this variable.
   *
   * @return array
   *   Comment about this variable.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('node_auto_expire.settings');
    $drupal_state = $this->state;

    foreach ($this->nodeType->loadMultiple() as $type => $name) {

      $code = $config->get('node_auto_expire_node_type') . $type;

      $form['nodetypes'][$type] = [
        '#type' => 'details',
        '#title' => $name->getOriginalId(),
        '#open' => $drupal_state->get($code . '_e', 0),
      ];

      $form['nodetypes'][$type][$code . '_e'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Expire'),
        '#default_value' => $drupal_state->get($code . '_e', 0),
      ];

      $form['nodetypes'][$type][$code . '_ex'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Extend auto expire on node updates'),
        '#default_value' => $drupal_state->get($code . '_ex', 0),
        '#description' => $this->t('Allow to extend auto expire after each node update by the specified period.'),
      ];

      $form['nodetypes'][$type][$code . '_d'] = [
        '#type' => 'number',
        '#title' => $this->t('Days'),
        '#min' => 0,
        '#field_suffix' => $this->t('days'),
        '#default_value' => $drupal_state->get($code . '_d', $config->get('node_auto_expire_days')),
        '#description' => $this->t('The number of days after an item was created when it will be automatically expired.'),
      ];

      $form['nodetypes'][$type][$code . '_w'] = [
        '#type' => 'number',
        '#title' => $this->t('Warn'),
        '#min' => 0,
        '#field_suffix' => $this->t('days'),
        '#default_value' => $drupal_state->get($code . '_w', $config->get('node_auto_expire_warn')),
        '#description' => $this->t('The number of days before the items expiration when a warning message is sent to the user. Set to 0 (zero) for no warnings.'),
      ];

      $form['nodetypes'][$type][$code . '_p'] = [
        '#type' => 'number',
        '#title' => $this->t('Purge'),
        '#min' => 0,
        '#field_suffix' => $this->t('days'),
        '#default_value' => $drupal_state->get($code . '_p', $config->get('node_auto_expire_purge')),
        '#description' => $this->t('The number of days after an item has expired when it will be purged from the database. Set to 0 (zero) for no purge.'),
      ];

    }

    $notification_types = ['warn', 'expired'];

    foreach ($notification_types as $notification_type) {

      $form['email'][$notification_type] = [
        '#type' => 'details',
        '#title' => ($notification_type == 'warn' ? $this->t('Expiration Warning') : $this->t('Expired')) . ' ' . $this->t('Notification Email'),
        '#open' => FALSE,
        '#description' => $this->t("Template of email sent @expire. You can use the following variables: @type - content type, @title - title of content, @url - URL of content, @days - number of days before items expire, @date - the expiration date of the item, @daysleft @site - site name, @siteurl - site URL",
          [
            '@expire' => $notification_type == 'warn' ? 'when content is about to expire' : 'right after the content has been expired',
            '@daysleft' => $notification_type == 'warn' ? '@daysleft - time left until the expiration date,' : '',
          ]
        ),
      ];

      $form['email'][$notification_type][$config->get('node_auto_expire_email') . $notification_type . '_subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Email Subject'),
        '#default_value' => $drupal_state->get($config->get('node_auto_expire_email') . $notification_type . '_subject') ?: ($notification_type == 'warn' ? $this->t('Content @type is about to expire') : $this->t('Content @type has been expired')),
        '#description' => $this->t('Leave empty to disable @expire Notifications.',
          [
            '@expire' => $notification_type == 'warn' ? 'Expiration Warning' : 'Expired',
          ]
        ),
      ];

      $form['email'][$notification_type][$config->get('node_auto_expire_email') . $notification_type . '_body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Email Body'),
        '#default_value' => $drupal_state->get($config->get('node_auto_expire_email') . $notification_type . '_body') ?: $this->t("Your content @type '@title' @expire",
          [
            '@expire' => $notification_type == 'warn' ? "will expire in @daysleft \r\n\r\nPlease visit @site, if renewal is required:\r\n@url" : "has been expired.\r\n\r\nPlease visit @site to add new content:\r\n@siteurl",
          ]
        ),
      ];

    }

    $form['email']['cc'][$config->get('node_auto_expire_email') . 'bcc'] = [
      '#type' => 'email',
      '#title' => $this->t('BCC Address'),
      '#pattern' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
      '#placeholder' => 'domain@domain.com',
      '#description' => $this->t('An e-mail address to send blind carbon copy notifications. (Leave blank to not send BCC)'),
      '#default_value' => $drupal_state->get($config->get('node_auto_expire_email') . 'bcc', ''),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * Implement submitForm().
   *
   * @param array $form
   *   Comment about this variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Comment about this variable.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_settings = $form_state->getValues();

    foreach ($form_settings as $name => $setting) {

      if (strpos($name, 'auto_expire_') !== FALSE) {
        $this->state->set($name, $setting);
      }

    }

    parent::submitForm($form, $form_state);

  }

}
