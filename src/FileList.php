<?php

namespace Drupal\media_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;


class FileList {

  /**
  * Constructor
  *
  */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DatabaseFileUsageBackend $fileUsage ) {}



    /**
    * Gets the filename and uri for all  file entities.
    *
    * @return array
    *   An array of file information (id, name, filename, URI).
    */
    function getFileInfo() {
        $file_info = [];

        // 1. Load all file entities. You might want to filter by type (e.g., 'image', 'file')
        // for performance on large sites, but this loads all.
        $file_ids = $this->entityTypeManager->getStorage('file')->getQuery()
            ->accessCheck('TRUE')
            ->execute();

        $file_entities = File::loadMultiple($file_ids);

        foreach ($file_entities as $file_entity) {
            $file_uri = $file_entity->getFileUri();
            $filename = $file_entity->getFilename();

            $usage = $this->fileUsage->listUsage($file_entity);
            if ( !empty($usage)) {
                dpm($usage);
                break;
            }
            $file_info[] = [
                'file_id'   => $file_entity->id(),
                'filename'   => $filename,
                'uri'        => $file_uri,
            ];
        }

        return $file_info;
    }


}
