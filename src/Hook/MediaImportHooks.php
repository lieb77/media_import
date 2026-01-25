<?php

declare(strict_types=1);

namespace Drupal\media_import\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\Alter;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use Drupal\media_import\GeoTag;

/**
 * Implement hooks per Drupal 11 specs.
 */
class MediaImportHooks {
	use StringTranslationTrait;

	/**
	 * Implements hook_help().
	 */
	#[Hook('help')]
	public function help($route_name, RouteMatchInterface $route_match) {
		switch ($route_name) {
		case 'help.page.media_import':
			$output  = "<h2>Media Import Help</h2>";
			$output .= "<p>This module will import image files as media objects. ";
			$output .= "The files are first placed in a subdiectory of sites/default/files/. ";
			$output .= "Media can then be tagged with a category, a family event, ";
			$output .= "or a bicycle tour.</p>";
			return $output;
		}
		return null;
	}
	
	
	/**
	 * Dynamically assigns the best available parent link
	 * to our media import menu link
	 */
	#[Hook('menu_links_discovered_alter')]
	public function menuLinksDiscoveredAlter(array &$links): void {

		// 1. Define your custom link properties
		$my_link = [
			'title' 		=> $this->t('Import images as Media'),
			'description' => $this->t('Add media from images on disk and apply categories'),
			'route_name' 	=> 'media_import.media',
			'provider' 	=> 'media_import',
			'weight' 		=> 10,
		];
		
		
		// 2. List potential parents in order of preference
		$potential_parents = [
			'admin_toolbar_tools.extra_links:media_page', // Admin Toolbar Extra
			'view.media.page_1',                          // Core Media View (Table)
			'view.media_library.page_1',                  // Media Library View (Grid)
			'entity.media.collection',                    // Media Entity Route
			'system.admin_content',                       // Fallback: Main Content Menu
		];
		
		// 3. Detect and assign the first parent that exists on the site
		foreach ($potential_parents as $parent_id) {
			if (isset($links[$parent_id])) {
				$my_link['parent'] = $parent_id;
				break;
		  	}
		}
		
		$links['media_import.media_sublink'] = $my_link;
	}
	
	/**
	 * Implements hook_media_presave().
	 * Automatically extracts GPS data when a new media image is created via the UI.
	 */
	#[Hook('media_presave')]
	public function mediaPresave(MediaInterface $media): void {
		// 1. Only target the 'image' bundle.
		if ($media->bundle() !== 'image') {
			return;
		}

		// 2. Only process if the field is currently empty.
		// This prevents re-geocoding every time you edit the media title.
		if ($media->get('field_taken')->isEmpty()) {
	
			$source_field = $media->get('field_media_image')->first();
			if ($source_field && $source_field->entity) {
				$file_uri = $source_field->entity->getFileUri();
				$fullPath = \Drupal::service('file_system')->realpath($file_uri);
				
				// 3. Call your existing service.
				$tagger = \Drupal::service('media_import.geotag');
				$geoData = $tagger->process($fullPath);
				
				if ($geoData) {
					$media->set('field_taken',     $geoData['date']);
					$media->set('field_location',  $geoData['full']);
					$media->set('field_longitude', $geoData['lng']);
					$media->set('field_latitude',  $geoData['lat']);
			
					$lineage_ids = [];
			
					if (!empty($geoData['country'])) {
						$country_id = $tagger->getOrCreateTerm($geoData['country'], 'geography', 0);
						$lineage_ids[] = $country_id;
			
						//  State / Province
						if (!empty($geoData['state'])) {
							$state_id = $tagger->getOrCreateTerm($geoData['state'], 'geography', $country_id);
							$lineage_ids[] = $state_id;
			
							// 3. City / Town / Hamlet
							if (!empty($geoData['city'])) {
								$city_id = $tagger->getOrCreateTerm($geoData['city'], 'geography', $state_id);
								$lineage_ids[] = $city_id;
							}
						}
					}
					// Save the whole array to the extity reference field
					$media->set('field_place', $lineage_ids);
				}
			}
		}
	}
  
  
	
	
// End of class.
}
