<?php

declare(strict_types=1);

namespace Drupal\media_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_import\MediaList;

/**
 *
 */
final class MediaImportController extends ControllerBase {

    public function __construct(protected MediaList $lister) {}


    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static {
        return new static(
            $container->get('media_import.list'),
        );
    }

    public function getMedia(){
        $media = $this->lister->getMediaInfo();

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

//end-of-class
}
