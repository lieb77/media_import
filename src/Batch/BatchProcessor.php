<?php

namespace Drupal\media_import\Batch;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class BatchProcessor {
	use StringTranslationTrait;

  	public static function processStep($filename, $path, $category, $context_id, &$context) {
		// Re-instantiate your service inside the batch request
		$importer = \Drupal::service('media_import.import');

		// We update the message for the UI progress bar
		$context['message'] = t('Importing: @file', ['@file' => $filename]);

		// Execute the actual logic for ONE file
		$result = $importer->importMedia($filename, $path, $category, $context_id);

		if ($result) {
			$context['results'][] = $filename;
		}
	}

	public static function finish($success, $results, $operations) {
		if ($success) {
			\Drupal::messenger()->addStatus(t('Successfully imported @count files.', [
				'@count' => count($results),
			]));
		} else {
			\Drupal::messenger()->addError(t('The import finished with errors.'));
		}

		// Redirect to the media collection
		$url = Url::fromRoute('entity.media.collection');

		// By setting this redirect in the finish function,
		// the browser will go here automatically after the progress bar finishes.
		return new \Symfony\Component\HttpFoundation\RedirectResponse($url->toString());
	}

}
