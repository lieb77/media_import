<?php

namespace Drupal\media_import;
use Drupal\media\Entity\Media;
use Drupal\media_import\GeoTag;

class GeoProcessor {

	public static function processItem($ids, &$context) {
	
		$geoTagger = \Drupal::getContainer()->get('media_import.geotag');
		
	    // Initialize results if first time
    	if (!isset($context['results']['processed'])) {
      		$context['results']['processed'] = 0;
    	}
    				
		foreach ($ids as $id) {
			$media = Media::load($id);
			$file_entity = $media->get('field_media_image')->entity;
			    
			if ($file_entity) {
  				// 2. Get the URI (e.g., public://2024-01/photo.jpg)
  				$uri = $file_entity->getFileUri();

  				// 3. Convert URI to an absolute local file path
  				$real_path = \Drupal::service('file_system')->realpath($uri);

  				if ($real_path && file_exists($real_path)) {
					
					$geoData  = $geoTagger->process($real_path);
					$media->set('field_taken',     $geoData['date']);
					$media->set('field_location',  $geoData['full']);
					$media->set('field_longitude', $geoData['lng']);
					$media->set('field_latitude',  $geoData['lat']);
					
					$lineage_ids = [];
					
					if (!empty($geoData['country'])) {
						$country_id = $geoTagger->getOrCreateTerm($geoData['country'], 'geography', 0);
						$lineage_ids[] = $country_id;
						
						//  State / Province
						if (!empty($geoData['state'])) {
							$state_id = $geoTagger->getOrCreateTerm($geoData['state'], 'geography', $country_id);
							$lineage_ids[] = $state_id;
					
							// 3. City / Town / Hamlet
							if (!empty($geoData['city'])) {
								$city_id = $geoTagger->getOrCreateTerm($geoData['city'], 'geography', $state_id);
								$lineage_ids[] = $city_id;
							}
						}
					}		
					// Save the whole array to the extity reference field
					$media->set('field_place', $lineage_ids);
					$media->save();							
				}
			}
		}
	}

	public static function finishBatch($success, $results, $operations) {
	  if ($success) {
		$count = count($results);
		\Drush\Drush::logger()->success(dt('Geocoding complete. @count images processed.', ['@count' => $count]));
	  }
	  else {
		\Drush\Drush::logger()->error('The batch finished with an error.');
	  }
	}

}