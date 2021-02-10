<?php

namespace Drupal\node_auto_expire\Form;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Component\Datetime\Time;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View of the Node Auto Expire expire form.
 */
class NodeAutoExpireExpireForm extends FormBase {

  /**
   * State Interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Date Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Translation Interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $dateTime;

  /**
   * NodeAutoExpireExpireForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State interface injection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager injection.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   Date formatter injection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Translation interface injection.
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection interface injection.
   * @param \Drupal\Component\Datetime\Time $date_time
   *   Date time interface injection.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entity_type_manager, DateFormatter $date_formatter, TranslationInterface $translation, Connection $connection, Time $date_time) {
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->stringTranslation = $translation;
    $this->connection = $connection;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the services required to construct this class.
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('string_translation'),
      $container->get('database'),
      $container->get('datetime.time')
    );
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'node_auto_expire_expire';
  }

  /**
   * Implements buildForm().
   *
   * @param array $form
   *   Comment about this variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Comment about this variable.
   * @param int $node
   *   Comment about this variable.
   *
   * @return array
   *   Comment about this variable.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {

    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node);
    $config = $this->config('node_auto_expire.settings');
    $drupal_state = $this->state;

    $expire = $this->nodeAutoExpireGetExpire($node->id());
    $code = $config->get('node_auto_expire_node_type') . $node->getType();
    $warn = $drupal_state
      ->get($code . '_w', $config->get('node_auto_expire_warn'));

    $form['#title'] = $node->getTitle();

    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['expire'] = [
      '#type' => 'value',
      '#value' => $expire,
    ];

    // Check if there is an expire date.
    // May be absent if node was created before the module was installed.
    if ($expire) {

      $request_time = $this->dateTime->getRequestTime();
      $date_formatter = $this->dateFormatter;

      if ($request_time < $expire) {

        $form['expireson'] = [
          '#markup' => '<p>' .
          $this->t('"@title" will be expired on @date (days left: @daysleft).',
              [
                '@title' => $node->getTitle(),
                '@date' => $date_formatter->format($expire, 'custom', 'd M Y H:i:s'),
                '@daysleft' => $date_formatter->formatInterval($expire - $request_time),
              ]
          ) .
          '</p>',
        ];

      }
      else {

        $form['expireson'] = [
          '#markup' => '<p>' .
          $this->t('"@title" has been expired on @date.',
            [
              '@title' => $node->getTitle(),
              '@date' => $date_formatter->format($expire, 'custom', 'd M Y H:i:s'),
            ]
          ) .
          '</p>',
        ];

      }

      if ($request_time > $expire - ($warn * 24 * 60 * 60)) {

        $days = $drupal_state
          ->get($code . '_d', $config->get('node_auto_expire_days'));
        $purge = $drupal_state
          ->get($code . '_p', $config->get('node_auto_expire_purge'));
        if ($purge > 0) {
          $form['extendby'] = [
            '#markup' => '<p>' .
            $this->stringTranslation
              ->formatPlural($purge, 'It can be extended within the next @count day after expiring.', 'It can be extended within the next @count days after expiring.') .
            '</p>',
          ];
        }

        // Actions.
        $form['actions'] = [
          '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->stringTranslation
            ->formatPlural($days, 'Extend for @count day', 'Extend for @count days'),
          '#button_type' => 'primary',
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];

      }
      else {

        if ($warn > 0) {
          $form['extendwhen'] = [
            '#markup' => '<p>' .
            $this->stringTranslation
              ->formatPlural($warn, 'You will be able to extend this publication 24 hours before the expiry time.', 'You will be able to extend this publication @count days before the expiry time.') .
            ' ' .
            $this->t('An email will be sent for the notification.') .
            '</p>',
          ];
        }
      }

    }
    else {

      $form['no_expire'] = [
        '#markup' => '<p>' . $this->t('"@title" does not have an expiration date.', [
          '@title' => $node->getTitle(),
        ]),
      ];
      $this->messenger()->addMessage(
        $this->t('Node Auto Expire. "@title" does not have an expiration date. This may have been caused because the "@title" was created before you installed Auto Expire.',
          [
            '@title' => $node->getTitle(),
          ]
        ),
        'warning'
      );

    }

    // Disable form caching.
    $form['#cache']['max-age'] = 0;

    return $form;

  }

  /**
   * Implement submitForm().
   *
   * @param array $form
   *   Comment about this variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Comment about this variable.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $connection = $this->connection;
    $config = $this->config('node_auto_expire.settings');
    $formState = $form_state->getValues();
    $node_storage = $this->entityTypeManager->getStorage('node');

    $node = $node_storage->load($formState['nid']);
    $node->setPublished(TRUE);
    $node->save();

    $expire = $formState['expire'];

    $days = $this->state
      ->get($config->get('node_auto_expire_node_type') . $node->getType() . '_d', $config->get('node_auto_expire_days'));
    $new_expire = max($this->dateTime
      ->getRequestTime(), $expire) + $days * 24 * 60 * 60;

    $connection->update('node_auto_expire')
      ->fields([
        'expire' => $new_expire,
        'warned' => 0,
      ])
      ->expression('extended', 'extended + 1')
      ->condition('nid', $node->id())
      ->execute();

    $link = Link::fromTextAndUrl(
      $this->t('view'),
      Url::fromUri('internal:/node/' . $node->id())
    )->toString();
    $this->logger('node_auto_expire')
      ->notice("The node with ID %node has been extended by @days days, " . $link,
        [
          '%node' => $node->id(),
          '@days' => $days,
        ]
      );

    $this->messenger()->addMessage($this->t('Extended by @days days',
      [
        '@days' => $days,
      ]
    ));

    $form_state->setRedirectUrl(Url::fromUri('internal:' . '/node/' . $node->id() . '/expire'));

  }

  /**
   * Restrict access to the Expiry actions.
   *
   * @param int $node
   *   Comment about this variable.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden
   *   Comment about this variable.
   */
  public static function access($node = NULL) {

    $user = Drupal::currentUser();
    $node = Node::load($node);

    // If node exists.
    if (is_object($node)) {
      $will_expire = Drupal::state()
        ->get(Drupal::config('node_auto_expire.settings')
          ->get('node_auto_expire_node_type') . $node->getType() . '_e', 0);
    }
    else {
      $will_expire = FALSE;
    }

    if ($will_expire) {
      return AccessResult::allowedIf($user->hasPermission('extend expiring all content') || ($user->hasPermission('extend expiring own content') && $user->id() > 0 && $node->getOwnerId() == $user->id()));
    }
    else {
      return AccessResult::forbidden();
    }

  }

  /**
   * Retrieves the expire record for given nid.
   *
   * @param int $nid
   *   Comment about this variable.
   *
   * @return mixed
   *   Comment about this variable.
   */
  public static function nodeAutoExpireGetExpire($nid) {

    $connection = Drupal::database();

    $query = $connection->select('node_auto_expire', 'ae')
      ->fields('ae', ['expire']);
    $query->condition('nid', $nid);

    return $query->execute()->fetchField();

  }

}
