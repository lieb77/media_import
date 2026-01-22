<?php

namespace Drupal\media_import\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;

class GeoProcessCommands extends DrushCommands {
	
	protected $entityTypeManager;
	
	public function __construct(EntityTypeManagerInterface $entityTypeManager) {
		parent::__construct();
		$this->entityTypeManager = $entityTypeManager;
	}
	
	/**
	* Reads EXIF directly from files and populates location taxonomy.
	*
	* @command geo:process
	* @aliases gp
	*/
	public function process() {
		$storage = $this->entityTypeManager->getStorage('media');
		$ids = $storage->getQuery()
			->condition('bundle', 'image')
			->accessCheck(FALSE)
			->execute();
		
		$this->output()->writeln(dt('Processing @count images...', ['@count' => count($ids)]));
		
		// Get the Exif factory service
		$exif_factory = \Drupal::service('exif.metadata.reader_factory');
		$factory = \Drupal::service('exif.metadata.reader_factory');
		$reader = $factory->getExifInterface();		
		
		foreach ($ids as $id) {
			$media = $storage->load($id);
			$file_entity = $media->get('field_media_image')->entity;

			if ($file_entity) {
  				// 2. Get the URI (e.g., public://2024-01/photo.jpg)
  				$uri = $file_entity->getFileUri();

  				// 3. Convert URI to an absolute local file path
  				$real_path = \Drupal::service('file_system')->realpath($uri);

  				if ($real_path && file_exists($real_path)) {
					$metadata = $reader->readMetadataTags($real_path);
	
					if(!empty($metadata['gps'])){
						$this->output()->writeln(dt('Found geotags for media id: @id', ['@id' => $id ]));
						
						// Get the data from the exif and gps fields
						$date = $metadata['exif']['datetimeoriginal'] ?? NULL;						
						$lat = $metadata['gps']['gpslatitude'];
						$lng = $metadata['gps']['gpslongitude'];
		       			
		       			// Save what we have so far
						$media->set('field_taken', $date);
						$media->set('field_longitude', $lng);
						$media->set('field_latitude', $lat);
		       			
		       			
		       			// Now get the location data from the open street maps service
		       			$data = $this->reverseGeocode($lat, $lng);		       		
		       		
		       			$full    = $data['display_name'];
		       			$address = $data['address'];
						$country = $address['country'] ?? NULL;
						$state   = $address['state'] ?? $address['region'] ?? $address['province'] ?? NULL;
						$county  = $address['county'] ?? NULL;
						$local   = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['hamlet'] ?? $address['suburb'] ?? NULL;
						$road    = $address['road'] ?? null;

						// Save the location field
						$media->set('field_location', $full);

						if ($address) {
							$lineage_ids = [];
							
							// 1. Country
							if (!empty($address['country'])) {
								$country_id = $this->getOrCreateTerm($address['country'], 'geography', 0);
								$lineage_ids[] = $country_id;
								
								// 2. State / Province
								$state_name = $address['state'] ?? $address['province'] ?? $address['region'] ?? NULL;
								if ($state_name) {
									$state_id = $this->getOrCreateTerm($state_name, 'geography', $country_id);
									$lineage_ids[] = $state_id;
									
									// 3. City / Town / Hamlet
									$city_name = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['hamlet'] ?? NULL;
									if ($city_name) {
										$city_id = $this->getOrCreateTerm($city_name, 'geography', $state_id);
										$lineage_ids[] = $city_id;
									}
								}
							}
						}
						// This is the magic part: Save the whole array to the field
						// Make sure your field_geography is set to "Unlimited" cardinality in the UI
						$media->set('field_place', $lineage_ids);
						$media->save();
						$this->output()->writeln(dt('Updated media id: @id', ['@id' => $id ]));
					}
				}
			}
		}
	}

	private function reverseGeocode($lat, $lng) {
		$client = \Drupal::httpClient();
		$url = "https://nominatim.openstreetmap.org/reverse";
		
		try {
			$response = $client->request('GET', $url, [
				'query' => [
					'format' => 'json',
					'lat' => $lat,
					'lon' => $lng,
					'zoom' => 18, // 5 = State level, 3 = Country level
				],
				'headers' => [
					'User-Agent' => 'DrupalMediaGeotag/1.0 (lieb@orchardware.net)',
				],
				'timeout' => 5, // Don't hang the script if the API is slow
			]);
			
			$data = json_decode($response->getBody()->getContents(), TRUE);
			return $data ?? NULL;
		}
		catch (\Exception $e) {
			$this->output()->writeln("Geocode error for $lat, $lng: " . $e->getMessage());
			return NULL;
		}
	}



	private function getOrCreateTerm($name, $vid, $parent_tid = 0) {
		$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
		
		$query = $term_storage->getQuery()
			->condition('name', $name)
			->condition('vid', $vid)
			->condition('parent', $parent_tid) // Ensure we find the right one (e.g., "Orange" in CA vs "Orange" in NSW)
			->accessCheck(FALSE);
		
		$tids = $query->execute();
		
		if (!empty($tids)) {
			return reset($tids);
		}
		
		$term = \Drupal\taxonomy\Entity\Term::create([
			'name' => $name,
			'vid' => $vid,
			'parent' => $parent_tid,
		]);
		$term->save();
		return $term->id();
	}
}