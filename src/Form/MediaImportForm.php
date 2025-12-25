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
use Drupal\media_import\Categories;

/**
 * Provides a Tour media form.
 */
final class MediaImportForm extends FormBase {

	protected $step = 1;
	protected $path;
	
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function __construct(
		protected Categories $categories,
		protected MediaImport $importer ) {}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
		  $container->get('media_import.categories'),
		  $container->get('media_import.import'),
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
			$categories = $this->categories->getCategories();
			$events     = $this->categories->getEvents();
			$tours      = $this->categories->getTours();
			
			$form['intro'] = [
				'#type'  => 'item',
				'#markup' => $this->t("<p>This form will import existing image files as media and tag them with a category.</p>"),
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
						':input[name="categories"]' => ['value' => '999'],
					],
				],			
			];
							
			// Dropdown to select existing event
			$events['999'] = "Create new event";
			$form['events'] = [
				'#type'  => 'select',
				'#title' => $this->t("Select a family event"),
				'#options' => $events,
				'#description' => $this->t("Select event name you would like assigned to these photos."),
				'#attributes' => ['id' => 'events'],
				'#states' => [
					// Show this textfield only if the category 'family' is selected above.
					'visible' => [
						// Don't mistake :input for the type of field or for a css selector --
						// it's a jQuery selector. 
						// You can always use :input or any other jQuery selector here, no matter 
						// whether your source is a select, radio or checkbox element.
						':input[name="categories"]' => ['value' => '12'],
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
						':input[name="categories"]' => ['value' => '71'],
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
		
			// See if we have a new category, else uses the selected events
		    // And stash it for later
		  	$this->category = (! empty($form_state->getValue('category'))) ?
				trim($form_state->getValue('category')) : $form_state->getValue('categories');
		
			// Confirm the directory exists
		  	$path = trim($form_state->getValue('media'));
		  			  	
		  	if ($this->importer->dirExists($path) === FALSE ) {
				$form_state->setErrorByName('media', $this->t("Directory %dir does not exist", ['%dir' => $path]));
		  	}
		  	
		  	// Save directory path
			$this->path = $path;
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
    	
			$this->importer->importMedia($this->category);
    
      		// Redirect to media
     	 	$path = '/admin/content/media';

			$validator = \Drupal::service('path.validator');
			$url_object = $validator->getUrlIfValid($path);
			$route_name = $url_object->getRouteName();
			$route_parameters = $url_object->getrouteParameters();
			
			$form_state->setRedirect($route_name, $route_parameters);
		}
	}

// End-of-class
} 
