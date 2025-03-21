<?php

declare(strict_types=1);

namespace Drupal\media_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\media_import\TourMedia;
use Drupal\media_import\TourMediaImport;

/**
 * Provides a Tour media form.
 */
final class TourMediaForm extends FormBase {
    protected $tours;
    protected $mediaimport;
    protected $tourid;
    protected $step = 1;


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'media_import_tour_form';
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

      $title = $this->t("Files to import for Tour " . $tname);



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

      $tourmedia = new TourMedia();
      $this->tours = $tourmedia->getTours();

      $form['intro'] = [
        '#type'  => 'item',
        '#markup' => $this->t("<p>This form will import existing image files as media and attach them to a tour.</p>"),
      ];

      $form['tour'] = [
        '#type'  => 'select',
        '#title' => $this->t("Tour"),
        '#options' => $this->tours,
        '#description' => $this->t("Select a tour to which you want to attach images"),
      ];

      $form['media'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Directory'),
        '#size' => 60,
        '#maxlength' => 128,
        '#description' => $this->t("Enter the directory which contains the images, relative to sites/default/files/"),
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
      $path = trim($form_state->getValue('media'));
      $this->tourid = $form_state->getValue('tour');
      $this->mediaimport = new TourMediaImport($path);

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
      $this->mediaimport->importMedia($this->tourid);

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
