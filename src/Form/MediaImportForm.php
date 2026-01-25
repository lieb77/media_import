<?php

declare(strict_types=1);

namespace Drupal\media_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;


use Drupal\media_import\MediaImport;
use Drupal\media_import\MediaTags;

/**
 * Provides a Tour media form.
 */
final class MediaImportForm extends FormBase {

	protected $step = 1; 	// Multi step form
	protected $path;		// Path where the files are
	protected $category;	// Selected from the Picture Types vocabulary
	protected $event;		// Selected from the Events vocabulary
	protected $tour;		// Select from Tour content

	protected $familyCategory;
	protected $tourCategory;


	/**
	 * {@inheritdoc}
	 *
	 */
	public function __construct(
		protected MediaTags   $tagger,
		protected MediaImport $importer) {

		// Get the config settings
        $config = $this->config('media_import.settings');
        $this->familyCategory = $config->get('family');
        $this->tourCategory   = $config->get('tour');

	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
		  $container->get('media_import.tags'),
		  $container->get('media_import.import')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFormId(): string {
		return 'media_import_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state): array {

		if ($this->step == 2) {

	  		$filenames = $this->importer->getFileNames();

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

		    // If the settings have not been set we must redirect to the settings form
		    if ( empty($this->familyCategory) || empty($this->tourCategory)) {
                $form['error'] = [
                    '#type'   => 'item',
                    '#markup' =>  $this->t("You must first configure this module in Comfig -> Media -> Media Import Settings"),
                ];
      		    return $form;
		    }


			$categories = $this->tagger->getCategories();
			$events     = $this->tagger->getEvents();
	//		$tours      = $this->tagger->getTours();

			$form['intro'] = [
				'#type'  => 'item',
				'#markup' => $this->t(
					"<p>This form will import existing image files as media and tag them with a category.</p>" .
					"<p>Family photos will be tagged with an event</p>" .
					"<p>Bicycle tour photos will be attached to a Tour</p>"	),
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

			// Dropdown to select existing category
			$categories['999'] = "New category";
			$form['categories'] = [
				'#type'  => 'select',
				'#title' => $this->t("Select an existing category"),
				'#options' => $categories,
				'#description' => $this->t("Select a category name you would like assigned to these photos."),
				'#attributes' => ['id' => 'categories'],
			];


			// Text field to get the category name
			$form['category'] = [
				'#type'  => 'textfield',
				'#title' => $this->t('Create a new category'),
				'#size' => 60,
				'#maxlength' => 128,
				'#description' => $this->t("Or enter a new category name you would like assigned to these photos."),
				'#states' => [
					// Show this textfield only if the category 'family' is selected above.
					'visible' => [
						// This uses a jQuery selector.
						':input[name="categories"]' => ['value' => '999'],
					],
				],
			];

			// Dropdown to select existing event
			$events['999'] = "New event";
			$form['events'] = [
				'#type'  => 'select',
				'#title' => $this->t("Select a family event"),
				'#options' => $events,
				'#description' => $this->t("Select event name you would like assigned to these photos."),
				'#attributes' => ['id' => 'events'],
				'#states' => [
					// Show this textfield only if the category 'family' is selected above.
					'visible' => [
						// This uses a jQuery selector.
						':input[name="categories"]' => ['value' => $this->familyCategory ],
					],
				],
			];

			// Text field to get a new event name
			$form['event'] = [
				'#type'  => 'textfield',
				'#title' => $this->t('Create a new event'),
				'#size' => 60,
				'#maxlength' => 128,
				'#description' => $this->t("Or enter a new event name you would like assigned to these photos."),
				'#states' => [
					// Show this textfield only if the category 'family' is selected above.
					'visible' => [
						':input[name="events"]' => ['value' => '999'],
						':input[name="categories"]' => ['value' => $this->familyCategory],
					],
				],

			];



			$form['tours'] = [
				'#type'  => 'select',
				'#title' => $this->t("Select a tour"),
				'#options' => $tours,
				'#description' => $this->t("Select a tour you would like assigned to these photos."),
				'#attributes' => ['id' => 'tours'],
				'#states' => [
					// Show this textfield only if the category 'Tour' is selected above.
					'visible' => [
						':input[name="categories"]' => ['value' => $this->tourCategory],
					],
				],
			];



			$form['actions'] = [
				'#type' => 'actions',
				'submit' => [
					'#type' => 'submit',
					'#value' => $this->t('Continue'),
				],
			];

//			$form['#attached']['library'][] = 'core/htmx';
		}
		return $form;
	}

	/**
	 * Form Validate
	 *
	 */
	public function validateForm(array &$form, FormStateInterface $form_state): void {
		if ($this->step == 1) {

			// Confirm the directory exists
		  	$path = trim($form_state->getValue('media'));

		  	if ($this->importer->dirExists($path) === FALSE ) {
				$form_state->setErrorByName('media', $this->t("Directory %dir does not exist", ['%dir' => $path]));
		  	}

		  	// Save directory path
			$this->path = $path;

			// See if we have a new category, else uses the selected category
		    // And stash it for later
		  	$this->category = (! empty($form_state->getValue('category'))) ?
				trim($form_state->getValue('category')) : $form_state->getValue('categories');

			// See if we have Family pictures
			if ($this->category == $this->familyCategory) {
				// See if we have a new event, else uses the selected events
				// And stash it for later
				$this->event = (! empty($form_state->getValue('event'))) ?
					trim($form_state->getValue('event')) : $form_state->getValue('events');
			}

			// See if we have Tour pictures
			if ($this->category == $this->tourCategory) {
				// And stash it for later
				$this->tour = $form_state->getValue('tours');
			}


		}
		else {
			$this->importer->dirExists($this->path);

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
    		$files = $this->importer->getFileNames();

    		$contextId = NULL;
    		switch ($this->category) {
    			case $this->familyCategory:
        			// Family pictures
        			$contextId = $this->event;
					break;
				case $this->tourCategory:
					// Touring pictures
					$contextId = $this->tour;
					break;
				default:
					$contextId = $this->category;
			}

			$operations = [];
			foreach ($files as $filename) {
				$operations[] = [
				'\Drupal\media_import\Batch\BatchProcessor::processStep',
					[
					  $filename,
					  $this->path,
					  $this->category,
					  $contextId,
					],
				];
			}

			$batch = [
				'title' => $this->t('Importing @count Photos', ['@count' => count($files)]),
				'operations' => $operations,
				'finished' => '\Drupal\media_import\Batch\BatchProcessor::finish',
			];

			batch_set($batch);

      		// Redirect to media
      		$form_state->setRedirect('entity.media.collection');
		}
	}

// End-of-class
}
