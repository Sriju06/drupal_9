<?php

namespace Drupal\vg_auto_archival\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class AutoArchivalForm.
 *
 * @package Drupal\vg_auto_archival\Form
 */
class AutoArchivalForm extends FormBase {

    /**
     * Enitity Type Manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
     */
    protected $entityTypeManager;

    /**
     * Logger channel.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected $logger;

    /**
     * Constructs a new AutoArchivalForm object.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Entity Type Manager.
     * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
     * Logger factory.
    */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactory $logger_factory) {
        $this->logger = $logger_factory->get('vg_auto_archival');
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('logger.factory')
        );
    }



    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'vg_auto_archival_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['auto_archive'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Archive Expired Content'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $now = new DrupalDateTime('now');
        $query = $this->entityTypeManager->getStorage('node')->getQuery();
        $nids = $query->condition('status', 1)
        ->condition('field_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<')  
        ->execute();

        $batch = array(
        'title' => t('Archiving Expired Node...'),
        'operations' => array(
            array(
            '\Drupal\vg_auto_archival\AutoArchival::setStateToArchive',
            array($nids, $this->logger),
            ),
        ),
        'finished' => '\Drupal\vg_auto_archival\AutoArchival::setStateToArchiveCallback',
        );

        batch_set($batch);
    }

}