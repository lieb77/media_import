<?php

namespace Drupal\media_import\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\media_import\GeoTag;

class GeoProcessCommands extends DrushCommands {
	
	public function __construct(
		protected EntityTypeManagerInterface $entityTypeManager) {
		parent::__construct();
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
		
		// Define the batch
  		$batch = [
			'title' 			=> dt('Processing Geo Data for @count images...', ['@count' => count($ids)]),
			'operations' 		=> [],
			'init_message' 		=> dt('Starting geo-import...'),
			'progress_message' 	=> dt('Processed @current out of @total.'),
			'error_message' 	=> dt('An error occurred during processing.'),
			'progressive'   	=> FALSE,
			'finished' 			=> '\Drupal\media_import\GeoProcessor::finishBatch',
		];

		// Break the IDs into chunks (e.g., 5 images per batch set)
		// Since we have a 1s delay per image, smaller chunks are better for feedback.
		foreach (array_chunk($ids, 1) as $chunk) {
			$batch['operations'][] = [
			  'Drupal\media_import\GeoProcessor::processItem', 
			  [$chunk]
			];
		}
		
		batch_set($batch);
  		drush_backend_batch_process(); // This triggers the progress bar in Drush
	}	

}