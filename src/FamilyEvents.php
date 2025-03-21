<?php

namespace Drupal\media_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;

class FamilyEvents{

  protected $events;

  /**
   * Constructor
   *
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {

    // Get the events vocabulary
    $vocabulary = $entity_type_manager->getStorage('taxonomy_vocabulary')->load('event');

    // Get the events terms
    $tids = $entity_type_manager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', $vocabulary->id())
        ->accessCheck(TRUE)
        ->execute();

    foreach ($tids as $tid) {
      $tname = Term::load($tid)->getName();
      $this->events[$tid] = $tname;
    }

  }


  /**
   * Get list of Tours
   *
   */
  public function getEvents() {
    return $this->events;
  }



}
