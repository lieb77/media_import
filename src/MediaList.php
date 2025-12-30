<?php

namespace Drupal\media_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;


class MediaList {

  /**
  * Constructor
  *
  */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}



    /**
    * Gets the filename and uri for all  media entities.
    *
    * @return array
    *   An array of file information (id, name, filename, URI).
    */
    function getMediaInfo() {
        $file_info = [];

        // 1. Load all media entities. You might want to filter by type (e.g., 'image', 'file')
        // for performance on large sites, but this loads all.
        $media_ids = $this->entityTypeManager->getStorage('media')->getQuery()
            ->condition('bundle', 'image')
            ->accessCheck('TRUE')
            ->execute();
        
        $media_entities = Media::loadMultiple($media_ids);

        foreach ($media_entities as $media_entity) {
            $file_entity = $media_entity->get('field_media_image')->entity;
            if ($file_entity instanceof File) {
                $file_uri = $file_entity->getFileUri();
                $filename = $file_entity->getFilename();

                $file_info[] = [
                    'media_id'   => $media_entity->id(),
                    'media_name' => $media_entity->getName(),
                    'filename'   => $filename,
                    'uri'        => $file_uri,
                ];
            }
        }

        return $file_info;
    }


}
