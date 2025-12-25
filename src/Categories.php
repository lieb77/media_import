<?php

namespace Drupal\media_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;

class Categories {

	/**
	* Constructor
	*
	*/
	public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

	/**
	* Get list of Categories
	*
	*/
	public function getCategories() {
		
		// Get the picture_type vocabulary
		$vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load('picture_type');
	
		// Get the picture type terms
		$tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
			->condition('vid', $vocabulary->id())
			->accessCheck(TRUE)
			->execute();
	
		foreach ($tids as $tid) {
		  $tname = Term::load($tid)->getName();
		  $categories[$tid] = $tname;
		}
	
		return $categories;
	}

	/**
     * Get list of Family Events
     *
     */
	public function getEvents() {
    
		// Get the events vocabulary
		$vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load('event');
		
		// Get the events terms
		$tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
			->condition('vid', $vocabulary->id())
			->accessCheck(TRUE)
			->execute();
		
		foreach ($tids as $tid) {
		  	$tname = Term::load($tid)->getName();
		  	$events[$tid] = $tname;
		}
		
		return $events;		
	}




// End of Class
}
