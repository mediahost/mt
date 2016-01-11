<?php

namespace App\ApiModule\Presenters;

use App\Extensions\FilesManager;
use App\Model\Facade\StockFacade;
use Drahak\Restful\Application\Responses\TextResponse;
use Drahak\Restful\IResource;
use Drahak\Restful\Mapping\NullMapper;

class ExportProductsPresenter extends BasePresenter
{

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var FilesManager @inject */
	public $filesManager;

	public function actionReadHeureka()
	{
		proc_nice(19);
		ini_set('max_execution_time', 1500);

		if (!$this->settings->modules->heureka->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else if (!in_array($this->translator->getLocale(), (array) $this->settings->modules->heureka->locales)) {
			$this->resource->state = 'error';
			$this->resource->message = 'This language is not supported';
		} else {
			$locale = $this->translator->getLocale();
			$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_HEUREKA_STOCKS, $locale);
			if (is_file($filename)) {
				$content = file_get_contents($filename);
				$response = new TextResponse($content, new NullMapper(), IResource::XML);
				$this->sendResponse($response);
			} else {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing \'' . $locale . '\' translation for this export';
			}
		}
	}

	public function actionReadZbozi()
	{
		proc_nice(19);
		ini_set('max_execution_time', 1500);

		if (!$this->settings->modules->zbozi->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else if (!in_array($this->translator->getLocale(), (array) $this->settings->modules->zbozi->locales)) {
			$this->resource->state = 'error';
			$this->resource->message = 'This language is not supported';
		} else {
			$locale = $this->translator->getLocale();
			$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_ZBOZI_STOCKS, $locale);
			if (is_file($filename)) {
				$content = file_get_contents($filename);
				$response = new TextResponse($content, new NullMapper(), IResource::XML);
				$this->sendResponse($response);
			} else {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing \'' . $locale . '\' translation for this export';
			}
		}
	}

}
