<?php

namespace Drupal\media_import;

use Drupal\exif\ExifFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Use the exif module to extract geo data from an image file
 * and use th Nominatim service to provide location informatiom
 *
 */
class GeoTag {
	
	protected $reader; 
	protected $nominatimUrl = "https://nominatim.openstreetmap.org/reverse";
	protected  $email = "lieb@orchardware.net";
	
	/**
 	 * Constructor
	 */
	public function __construct(		
		protected ClientInterface $httpClient,
		protected ExifFactory $exifFactory,
	    protected EntityTypeManagerInterface $entityTypeManager,
		protected LoggerChannelInterface $logger,	
		) {
	    
	    $this->reader = $exifFactory->getExifInterface();	
	}
	
	/**
	* Reads EXIF directly from files
	*
	* @param $filePath : full path to the image file
	*/
	public function process($filePath) {

		// Get the metadat
		$metadata = $this->reader->readMetadataTags($filePath);

		// Get the date from the exif array
		if(!empty($metadata['exif']) && !empty($metadata['exif']['datetimeoriginal']) ){
			$date = $metadata['exif']['datetimeoriginal'];	
		}

		if(!empty($metadata['gps'])){
		// Get the data from the gps fields					
			$lat = $metadata['gps']['gpslatitude'];
			$lng = $metadata['gps']['gpslongitude'];
		
			// Now get the location data from the open street maps service
			$data = $this->reverseGeocode($lat, $lng);		       		
			
			if (!empty($data)) {
				//  Pull out the fields we want
				$full    = $data['display_name'];
				$address = $data['address'];
				$country = $address['country'] ?? NULL;
				$state   = $address['state'] ?? $address['region'] ?? $address['province'] ?? NULL;
				$county  = $address['county'] ?? NULL;
				$local   = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['hamlet'] ?? $address['suburb'] ?? NULL;
				$road    = $address['road'] ?? null;
			}
		}
		return [
			'lat'	  => $lat ?? NULL,
			'lng'	  => $lng ?? NULL,
			'date'    => $date ?? NULL,
			'full' 	  => $full ?? NULL,
			'country' => $country ?? NULL,
			'state'   => $state ?? NULL,
			'county'  => $county ?? NULL,
		    'local'	  => $local ?? NULL,
		    'road'    => $road ?? NULL,
		];
	}
			
	
	private function reverseGeocode($lat, $lng) {
		try {
			$response = $this->httpClient->get($this->nominatimUrl, [
				'query' => [
					'format' => 'json',
					'lat' => $lat,
					'lon' => $lng,
					'zoom' => 18, // 5 = State level, 3 = Country level
				],
				'headers' => [
					'User-Agent' => 'DrupalMediaGeotag/1.0 ($this->email)',
				],
				'timeout' => 5, // Don't hang the script if the API is slow
			]);
			
			$data = json_decode($response->getBody()->getContents(), TRUE);
			return $data ?? NULL;
		}
		catch (\Exception $e) {
			//("Geocode error for $lat, $lng: " . $e->getMessage());
			return NULL;
		}
	}
	
/**
	 * Get existing term or create a new one
	 *
	 */
	public function getOrCreateTerm($name, $vid, $parent_tid = 0) {
		$termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

		$query = $termStorage->getQuery()
			->condition('name', $name)
			->condition('vid', $vid)
			->condition('parent', $parent_tid) // Ensure we find the right one (e.g., "Orange" in CA vs "Orange" in NSW)
			->accessCheck(FALSE);

		$tids = $query->execute();

		if (!empty($tids)) {
			return reset($tids);
		}

		$term = Term::create([
			'name' => $name,
			'vid' => $vid,
			'parent' => $parent_tid,
		]);
		$term->save();
		return $term->id();
	}


}