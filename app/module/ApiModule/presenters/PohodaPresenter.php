<?php

namespace App\ApiModule\Presenters;

use App\Model\Facade\PohodaFacade;
use Exception;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Tracy\Debugger;

class PohodaPresenter extends BasePresenter
{

	const LOGNAME = 'pohoda_api';
	const PARAM_FILE = 'file';

	/** @var IRequest @inject */
	public $httpRequest;

	/** @var PohodaFacade @inject */
	public $pohodaFacade;
	
	/** TODO: move to module settings */
	private $allowedReadShortStock = TRUE;
	private $allowedReadOrders = TRUE;
	private $allowedCreateStore = TRUE;
	private $allowedCreateShortStock = TRUE;

	public function actionReadShortStock()
	{
		if (!$this->allowedReadShortStock) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		}
		$this->resource->stock = 'actionReadShortStock ' . rand(1, 100);
	}

	public function actionReadOrders()
	{
		if (!$this->allowedReadOrders) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		}
		$this->setView();
	}

	public function actionCreateStore($use_gzip_upload)
	{
		if (!$this->allowedCreateStore) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		}
		try {
			$xml = $this->getFileContent($use_gzip_upload);
			$this->pohodaFacade->recieveStore($xml);
			$this->resource->state = 'ok';
		} catch (Exception $ex) {
			$this->resource->state = 'error';
			$this->resource->message = 'Error while processing XML';
			Debugger::log($ex->getMessage(), self::LOGNAME);
		}
		$this->setView('stockItemResponse');
	}

	public function actionCreateShortStock($use_gzip_upload)
	{
		if (!$this->allowedCreateShortStock) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		}
		try {
			$xml = $this->getFileContent($use_gzip_upload);
			$this->pohodaFacade->recieveShortStock($xml);
			$this->resource->state = 'ok';
		} catch (Exception $ex) {
			$this->resource->state = 'error';
			$this->resource->message = 'Error while processing XML';
			Debugger::log($ex->getMessage(), self::LOGNAME);
		}
		$this->setView('stockItemResponse');
	}

	private function getFileContent($gzip)
	{
		$content = NULL;
		$files = $this->httpRequest->getFiles();
		if (array_key_exists(self::PARAM_FILE, $files)) {
			$file = $files[self::PARAM_FILE];
			if ($file instanceof FileUpload && $file->isOk()) {
				$content = $gzip ? $this->ungzip($file->getTemporaryFile()) : $file->getContents();
			}
		}
		return $content;
	}

	private function ungzip($file)
	{
		$content = NULL;
		$handle = gzopen($file, 'r');
		while (!gzeof($handle)) {
			$content .= gzgets($handle, 10000);
		}
		gzclose($handle);
		return $content;
	}

}
