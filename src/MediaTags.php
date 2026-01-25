<?php

namespace Drupal\media_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

class MediaTags {

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






// End of Class
}
