<?php

namespace Drupal\media_import;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media_import\GeoTag;

class MediaImport {

	protected $files;
	protected $directory;
	protected $direxists = TRUE;
	protected $category;

	protected $familyCategory;
	protected $tourCategory;

	/**
 	 * Constructor
	 */
	public function __construct(
	    protected FileSystemInterface $fileSystem,
	    protected ConfigFactoryInterface $configFactory,
	    protected EntityTypeManagerInterface $entityTypeManager,
	    protected GeoTag $geoTagger,
	    ) {

		// Get the config settings
        $config = $configFactory->get('media_import.settings');
        $this->familyCategory = $config->get('family');
        $this->tourCategory   = $config->get('tour');

	}

	/**
	 * Import the files as Media
	 *
	 * @param $filename
	 * @param $category
	 * @param $context_id either event tour or null
	 *
	 *
	 */
	public function importMedia($filename, $path, $category, $context_id) {

	   $this->directory = "public://" . $path;

		//See if we're using an existing category or create a new one
		$tid = is_numeric($category) ? $category : $this->create_category($category);

		$this->category = $tid;

		// Create the file entity
		$file = $this->create_file_entity($filename);

		// Get the full path
		$uri = $file->getFileUri();
		$fullPath = $this->fileSystem->realpath($uri);

		// Get GeoTags
		$geoData = $this->geoTagger->process($fullPath);

		// Create the media entity
		$media = $this->create_media_entity($file, $context_id);

		// Update media with GeoTags
		$this->addGeotags($media, $geoData);
	}



	/**
	 *
	 * @param string $path
	 *  File path relative to public://
	 *
	 * @return
	 *  A list of filename or FALSE if Directory does not exist
	 *
	 *
	 */
	public function getFileNames() {

		try {
			$this->files = $this->get_filenames_in_directory($this->directory);
		}
		catch(NotRegularDirectoryException $e) {
			$this->direxists = FALSE;
		}
		return $this->files;
  	}

	/**
	 * Return True if directory exits and is readable
	 *
	 */
	public function dirExists($path) {
		$this->directory = "public://" . $path;

		$dir = $this->fileSystem->realpath($this->directory);
		return is_dir($dir) && is_readable($dir);

	}



	/************* Private functions ****************/

	/**
	 * Creates a Drupal file entity from an image file on disk.
	 *
	 * @param string $filepath
	 *   The absolute path to the image file on the server.
	 *
	 * @return \Drupal\media\Entity\File
	 *   The created File entity object, or null if creation failed.
	 *
	*/
	private function create_file_entity($filename) {
		$file = File::create([
			'filename' => $filename,
			'uri' => $this->directory . '/' . $filename,
			'status' => 1,
			'uid' => 1,
		]);
		$file->save();
		return $file;
	}

	/**
	 * Add term to picture_type vocabulary
	 *
	 * it appears vocabularies are referenced by name:
	 *
	 * @param string $category
	 *
	 * @return number $termId
	 *  tagret_id of new taxonomy term
	 *
	*/
	private function create_category($category){

		// Create the term
		$term = Term::create([
			'vid' => 'picture_type',
			'name' => $category,
		]);
		$term->save();
		return $term->id();
	}

	/**
	 * Add term to Event vocabulary
	 *
	 * it appears vocabularies are referenced by name: 'event'
	 *
	 * @param string $eventName
	 *
	 * @return number $termId
	 *  tagret_id of new taxonomy term
	 *
	*/
	private function create_event($event){

		// Create the term
		$term = Term::create([
			'vid' => 'event',
			'name' => $event,
		]);
		$term->save();
		return $term->id();
	}


	/**
	 * Creates a Drupal media entity from an image file on disk.
	 *
	 * @param string $file
	 *   A \Drupal\media\Entity\File
	 *
	 * @param int $tourid
	 *  The nid of the tour node
	 *
	 * @return \Drupal\media\Entity\Media|null
	 *   The created media entity object, or null if creation failed.
	 */
	private function create_media_entity(File $file, $id){

		// We have three types we could be creating
		switch ($this->category) {
			case $this->familyCategory :
				// Family photo
				$alt   = 'Family photo';
				$event = $id;
				break;
			case $this->tourCategory :
				// Touring
				$alt   = 'Touring photo';
				$tour  = $id;
				break;
			default:
				$alt = 'Imported photo';
		}

		// Create a new Media entity.
		$media = Media::create([
			'bundle'			=> 'image',
			'uid' 				=> 1,
			'name' 				=> $file->getFilename(),
			'field_media_image' => [
				'target_id' => $file->id(),
				'alt' 		=> $alt,
			],
			'field_category' => [
				'target_id' => $this->category,
			],
		]);
		if ($this->category == $this->familyCategory) {
			$media->set('field_event', $event);
		}
		if ($this->category == $this->tourCategory) {
			$media->set('field_tour', $tour);
        }


		try {
			$media->save();
		}
		catch (\Exception $e) {
			\Drupal::logger('add_media')->error('Failed to save media entity: @message', ['@message' => $e->getMessage()]);
			return null;
		}

		return $media;
	}

	/**
	 * Gets an array of filenames in a given directory.
	 *
	 * @param string $directory
	 *   The path to the directory.
	 *
	 * @return array
	 *   An array of filenames.
	 */
	private function get_filenames_in_directory(string $directory): array {
		$file_system = \Drupal::service('file_system');
		$filenames = [];

		$files = $file_system->scanDirectory($directory, '/.*/');

		foreach ($files as $file) {
			$filenames[] = $file->filename;
		}

		return $filenames;
	}

	/**
	 * Add GeoTags
	 *
	 * @param entity $media
	 * @param array $geoData
	 *
	 */
	private function addGeotags($media, $geoData){

		$media->set('field_taken',     $geoData['date']);
		$media->set('field_location',  $geoData['full']);
		$media->set('field_longitude', $geoData['lng']);
		$media->set('field_latitude',  $geoData['lat']);

		$lineage_ids = [];

		if (!empty($geoData['country'])) {
			$country_id = $this->getOrCreateTerm($geoData['country'], 'geography', 0);
			$lineage_ids[] = $country_id;

			//  State / Province
			if (!empty($geoData['state'])) {
				$state_id = $this->getOrCreateTerm($geoData['state'], 'geography', $country_id);
				$lineage_ids[] = $state_id;

				// 3. City / Town / Hamlet
				if (!empty($geoData['city'])) {
					$city_id = $this->getOrCreateTerm($geoData['city'], 'geography', $state_id);
					$lineage_ids[] = $city_id;
				}
			}
		}
		// Save the whole array to the extity reference field
		$media->set('field_place', $lineage_ids);
		$media->save();
	}

	/**
	 * Get existing term or create a new one
	 *
	 */
	private function getOrCreateTerm($name, $vid, $parent_tid = 0) {
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


// End-of-class
}
