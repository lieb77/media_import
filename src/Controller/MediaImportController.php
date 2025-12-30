<?php

declare(strict_types=1);

namespace Drupal\media_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_import\MediaList;
use Drupal\media_import\FileList;


/**
 *
 */
final class MediaImportController extends ControllerBase {

    public function __construct(
        protected MediaList $mediaLister,
        protected FileList  $fileLister) {}


    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static {
        return new static(
            $container->get('media_import.list'),
            $container->get('media_import.files'),
        );
    }

    public function getMedia(){
        $media = $this->mediaLister->getMediaInfo();

        $header = [
            $this->t("Id"),
            $this->t("Media name"),
            $this->t("File name"),
            $this->t("Uri")
        ];


        // Construct the render array for the table.
        $build['table'] = [
          '#type'   => 'table',
          '#header' => $header,
          '#rows'   => $media,
        ];

        return $build;

    }


    public function getFiles(){
        $files = $this->fileLister->getFileInfo();

        $header = [
            $this->t("Id"),
            $this->t("File name"),
            $this->t("Uri")
        ];


        // Construct the render array for the table.
        $build['table'] = [
          '#type'   => 'table',
          '#header' => $header,
          '#rows'   => $files,
        ];

        return $build;

    }



//end-of-class
}
