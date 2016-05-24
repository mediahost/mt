<?php

namespace App\ApiModule\Presenters;

use App\Extensions\FilesManager;
use App\Model\Facade\StockFacade;
use Drahak\Restful\Application\Responses\TextResponse;
use Drahak\Restful\IResource;
use Drahak\Restful\Mapping\NullMapper;
use Tracy\Debugger;

class ExportProductsPresenter extends BasePresenter
{

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var FilesManager @inject */
	public $filesManager;
	
	/** Priority CPU using */
	public function actionReadHeureka()
	{
		proc_nice(19);
		
		Debugger::timer('read-heureka');
		Debugger::log('start', 'read-heureka-start');

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

				$timer = Debugger::timer('read-heureka');
				Debugger::log($timer, 'read-heureka-stop');

				$this->sendResponse($response);
			} else {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing \'' . $locale . '\' translation for this export';
			}
		}

		$timer = Debugger::timer('read-heureka');
		Debugger::log($timer, 'read-heureka-stop');
	}

	/** Priority CPU using */
	public function actionReadZbozi()
	{
		proc_nice(19);

		Debugger::timer('read-zbozi');
		Debugger::log('start', 'read-zbozi-start');

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

				$timer = Debugger::timer('read-zbozi');
				Debugger::log($timer, 'read-zbozi-stop');

				$this->sendResponse($response);
			} else {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing \'' . $locale . '\' translation for this export';
			}
		}

		$timer = Debugger::timer('read-zbozi');
		Debugger::log($timer, 'read-zbozi-stop');
	}

}
