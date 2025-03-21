<?php

declare(strict_types=1);

namespace Drupal\media_import\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implement hooks per Drupal 11 specs.
 */
class MediaImportHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.pagetour_media':
        $output  = "<h2>Media Import Help</h2>";

        return $output;
    }
  }

  // End of class.
}
