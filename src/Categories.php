<?php

namespace Drupal\media_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

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
	
	/**
	 * Get the list of Tours
	 *
	 */
	public function getTours() {
	
		$ids = $this->entityTypeManager->getStorage('node')->getQuery()
			->condition('type', 'tour')
			->accessCheck('TRUE')
			->sort('field_start_date')
			->execute();
	
		foreach ($ids as $tid) {
		  $tname = Node::load($tid)->getTitle();
		  $tours[$tid] = $tname;
		}
	
		return $tours;
	}

// End of Class
}
