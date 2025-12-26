<?php

declare(strict_types=1);

namespace Drupal\media_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\media_import\MediaTags;


/**
 * Configure Media Import settings for this site.
 */
final class SettingsForm extends ConfigFormBase {


	/**
	 * {@inheritdoc}
	 *
	 */
	public function __construct(
		protected MediaTags   $tagger ) {}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
		  $container->get('media_import.tags'),
		);
	}


    /**
    * {@inheritdoc}
    */
    public function getFormId(): string {
        return 'media_import_settings';
    }

    /**
    * {@inheritdoc}
    */
    protected function getEditableConfigNames(): array {
        return ['media_import.settings'];
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state): array {

        // Get the list of categories
        $categories = $this->tagger->getCategories();

        // IF the config is already saved
        $family = $this->config('media_import.settings')->get('family');
        $tour   = $this->config('media_import.settings')->get('tour');

        // Select the Family category
        $form['family'] = [
            '#type'  => 'select',
            '#title' => $this->t("Select the category for Family pictures"),
            '#options' => $categories,
            '#description' => $this->t("Select the category for Family pictures"),
            '#attributes' => ['id' => 'categories'],
        ];
        if (!empty($family)){
            $form['family']['#default_value'] = $family;
        }

        // Select the Family category
        $form['tour'] = [
            '#type'  => 'select',
            '#title' => $this->t("Select the category for Touring pictures"),
            '#options' => $categories,
            '#description' => $this->t("Select the category for Touring pictures"),
            '#attributes' => ['id' => 'categories'],
        ];
        if (!empty($tour)){
            $form['tour']['#default_value'] = $tour;
        }


        return parent::buildForm($form, $form_state);

    }

    /**
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state): void {

       if ($form_state->getValue('family') == $form_state->getValue('tour')) {
            $form_state->setErrorByName(
               'message',
               $this->t('They cannot be the same.'),
             );
           }

        parent::validateForm($form, $form_state);
    }

    /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state): void {
        $this->config('media_import.settings')
          ->set('family', $form_state->getValue('family'))
          ->set('tour', $form_state->getValue('tour'))
          ->save();
        parent::submitForm($form, $form_state);
    }

}
