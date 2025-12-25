<?php

declare(strict_types=1);

namespace Drupal\media_import\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\Alter;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implement hooks per Drupal 11 specs.
 */
class MediaImportHooks {
	use StringTranslationTrait;

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
	
	
	/**
	 * Dynamically assigns the best available parent link.
	 */
	#[Hook('menu_links_discovered_alter')]
	public function menuLinksDiscoveredAlter(array &$links): void {

		// 1. Define your custom link properties
		$my_link = [
			'title' 		=> $this->t('Import images as Media'),
			'description' => $this->t('Add media from images on disk and apply categories'),
			'route_name' 	=> 'media_import.media',
			'provider' 	=> 'media_import',
			'weight' 		=> 10,
		];
		
		
		// 2. List potential parents in order of preference
		$potential_parents = [
			'admin_toolbar_tools.extra_links:media_page', // Admin Toolbar Extra
			'view.media.page_1',                         // Core Media View (Table)
			'view.media_library.page_1',                 // Media Library View (Grid)
			'entity.media.collection',                    // Media Entity Route
			'system.admin_content',                      // Fallback: Main Content Menu
		];
		
		// 3. Detect and assign the first parent that exists on the site
		foreach ($potential_parents as $parent_id) {
		  if (isset($links[$parent_id])) {
			$my_link['parent'] = $parent_id;
			break;
		  }
		}
		
		$links['media_import.media_sublink'] = $my_link;
	}
	
// End of class.
}
