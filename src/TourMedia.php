<?php

namespace Drupal\media_import;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Core\File\Exception\NotRegularDirectoryException;


class TourMedia {

  protected $tours;

  /**
   * Constructor
   *
   */
  public function __construct() {
    $ids = \Drupal::entityQuery('node')
        ->condition('type', 'tour')
        ->accessCheck('TRUE')
        ->sort('field_start_date')
        ->execute();

    foreach ($ids as $tid) {
      $tname = Node::load($tid)->getTitle();
      $this->tours[$tid] = $tname;
    }

  }


  /**
   * Get list of Tours
   *
   */
  public function getTours() {
    return $this->tours;
  }

   /**
     * Get list of media
     *
     */
   public function getMedia($tourid) {
        $ids = \Drupal::entityQuery('media')
            ->condition('bundle', 'image')
            ->condition('field_tour', $tourid)
            ->accessCheck('TRUE')
            ->execute();

        $medias = Media::loadMultiple($ids);
        return $medias;
    }


}
