<?php

declare(strict_types=1);

namespace Drupal\media_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\media_import\FamilyEvents;
use Drupal\media_import\FamilyMediaImport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Tour media form.
 */
final class FamilyMediaForm extends FormBase {
    protected $mediaimport;
    protected $event;
    protected $step = 1;
    protected $familyEvents;


  /**
   * Constructor
   *
   */
  public function __construct(FamilyEvents $familyEvents  ) {
    $this->familyEvents = $familyEvents;
  }

/**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_import.family'),
    );
  }

/**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'media_import_family_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    if ($this->step == 2) {
      $filenames = $this->mediaimport->getFileNames();
      $tname = $this->tours[$this->tourid];

      foreach ($filenames as $file) {
        $list .= "<br />" . $file ;
      }

      $title = $this->t("Files to import" . $tname);

      $form['files'] = [
        '#type'   => 'item',
        '#title'  => $title,
        '#markup' => $list,
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Import Media'),
        ],
      ];
    }

    else {
       $events = $this->familyEvents->getEvents();

       $form['intro'] = [
          '#type'  => 'item',
          '#markup' => $this->t("<p>This form will import existing image files as media and acatagorize them as family photos.</p>"),
        ];

        // Text field to get the directory path
        $form['media'] = [
          '#type'  => 'textfield',
          '#title' => $this->t('Directory'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE,
          '#description' => $this->t("Enter the directory which contains the images, relative to sites/default/files/"),
        ];

        // Dropdown to select existing event
        $form['events'] = [
          '#type'  => 'select',
          '#title' => $this->t("Select an existing events"),
          '#options' => $events,
          '#description' => $this->t("Select event name you would like assigned to these photos."),
        ];

        // Text field to get the event name
        $form['event'] = [
          '#type'  => 'textfield',
          '#title' => $this->t('Or create a new event'),
          '#size' => 60,
          '#maxlength' => 128,
          '#description' => $this->t("Or enter a new event name you would like assigned to these photos."),
        ];


        $form['actions'] = [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Continue'),
          ],
        ];
      }
    return $form;
  }

  /**
   * Instantiate a TourMedia object which will return a list of filenames
   *  or Flase if the direcory does not exist,
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($this->step == 1) {

      // See if we have a new event, else uses the selected events
      // And stash it for later
      $this->event = (! empty($form_state->getValue('event'))) ?
        trim($form_state->getValue('event')) : $form_state->getValue('events');


      $path = trim($form_state->getValue('media'));
      $this->mediaimport = new FamilyMediaImport($path);

      if ($this->mediaimport->dirExists() === FALSE ) {
        $form_state->setErrorByName('media', $this->t("Directory %dir does not exist", ['%dir' => $path]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->step == 1) {
      $this->step = 2;
      $form_state->setRebuild();
    }
    else {
      $this->mediaimport->importMedia($this->event);

      // Redirect to media
      $path = '/admin/content/media';

      $validator = \Drupal::service('path.validator');
      $url_object = $validator->getUrlIfValid($path);
      $route_name = $url_object->getRouteName();
      $route_parameters = $url_object->getrouteParameters();

      $form_state->setRedirect($route_name, $route_parameters);
    }
  }


} // End-of-class
