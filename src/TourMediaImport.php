<?php

namespace Drupal\media_import;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\File\Exception\NotRegularDirectoryException;


class TourMediaImport {

  protected $files;
  protected $directory;
  protected $direxists = TRUE;

  // The taxonomy ID for Ride which we set on the Media entity
  const CATEGORY = 10;

  /**
   * Constructor
   *
   * @param string $path
   *    File path relative to public://
   *
   * @return
   *    A list of filename or FALSE if Directory does not exist
   *
   */
  public function __construct($path) {

    // Read the directory and get a list of files
    $this->directory = "public://" . $path;

    try {
      $this->files = $this->get_filenames_in_directory($this->directory);
    }
    catch(NotRegularDirectoryException $e) {
      $this->direxists = FALSE;
    }

  }

  /**
   * Return directory exits
   *
   */
  public function dirExists() {
    return $this->direxists;
  }




  /**
   * Return list of file names
   *
   */
  public function getFileNames() {
    return $this->files;
  }


  /**
   * Import the files as Media
   *
   * @param int $tourid
   *    nid of Tour node
   *
   * @return
   *  Success or not
   *

   */
  public function importMedia($tourid) {

    // Loop through the files and save as entities
    foreach ($this->files as $filename) {
        $file = $this->create_file_entity($filename);
        $media = $this->create_media_entity($file, $tourid);
    }
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
   * Creates a Drupal media entity from an image file on disk.
   *
   * @param string $file
   *   A \Drupal\media\Entity\File
   *
   * @param int $tourid
   *    The nid of the tour node
   *
   * @return \Drupal\media\Entity\Media|null
   *   The created media entity object, or null if creation failed.
   */
  private function create_media_entity(\Drupal\file\Entity\File $file, $tourid){

    // Create a new Media entity.
    $media = Media::create([
        'bundle'=> 'image',
        'uid' => 1,
        'name' => $file->getFilename(),
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => t('Bicycle touring photo'),
        ],
        'field_category' => [
          'target_id' => $this::CATEGORY,
        ],

        'field_tour' => [
          'target_id' => $tourid,
        ],
      ]);

      try {
          $media->save();
      } catch (\Exception $e) {
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
  //    if ($file->type === FileSystemInterface::FILE_TYPE_FILE) {
          $filenames[] = $file->filename;
  //    }
    }

    return $filenames;
  }

}
