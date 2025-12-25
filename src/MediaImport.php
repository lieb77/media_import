<?php

namespace Drupal\media_import;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\File\Exception\NotRegularDirectoryException;


class MediaImport {

	protected $files;
	protected $directory;
	protected $direxists = TRUE;
	protected $category;

	/**
 	 * Constructor
	 */
	public function __construct(protected FileSystemInterface $fileSystem) {}
	
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

	/**
	 * Import the files as Media
	 *
	 * @param int $tourid
	 *  nid of Tour node
	 *
	 * @return
	 *  Success or not
	 *
	 */
	public function importMedia($category) {
	
		//See if we're using an existing category or create a new one
		// $tid = is_numeric($category) ? $category : $this->create_taxonomy_term($category);
	
	dpm($category);
	/*
		// Loop through the files and save as entities
		foreach ($this->files as $filename) {
			$file = $this->create_file_entity($filename);
			$media = $this->create_media_entity($file, $tid);
		}
*/
	}

	/**
	 * Import images for Family event
	 *
	 * @param int $event
	 *  tid of Event term
	 *
	 * @return
	 *  Success or not
	 *
	 */
	public function importFamily($event) {
		
		dpm($event);
		//See if we're using an existing category or create a new one
		//$tid = is_numeric($category) ? $category : $this->create_taxonomy_term($category);
	
	
		/*
		// Loop through the files and save as entities
		foreach ($this->files as $filename) {
			$file = $this->create_file_entity($filename);
			$media = $this->create_media_entity($file, $tid);
		}
		*/
	}

	/**
	 * Import images for Tour
	 *
	 * @param int $tour
	 *  nid of Tour node
	 *
	 * @return
	 *  Success or not
	 *
	 */
	public function importTour($tour) {
	
		dpm($tour);
		/*
		// Loop through the files and save as entities
		foreach ($this->files as $filename) {
			$file = $this->create_file_entity($filename);
			$media = $this->create_media_entity($file, $tid);
		}
	*/
	}


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
	private function create_taxonomy_term($category){
	
		// Create the term
		$term = Term::create([
			'vid' => 'picture_type',
			'name' => $category,
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
	private function create_media_entity(\Drupal\file\Entity\File $file, $tid){
	
		// Create a new Media entity.
		$media = Media::create([
			'bundle'=> 'image',
			'uid' => 1,
			'name' => $file->getFilename(),
			'field_media_image' => [
			'target_id' => $file->id(),
			'alt' => t('Family photo'),
			],
			'field_category' => [
			'target_id' => $this::CATEGORY,
			],	
			'field_event' => [
			'target_id' => $tid,
			],
		]);
		
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

// End-of-class
}
