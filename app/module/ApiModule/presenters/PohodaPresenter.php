<?php

namespace App\ApiModule\Presenters;

use App\Model\Facade\PohodaFacade;
use Exception;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Tracy\Debugger;

class PohodaPresenter extends BasePresenter
{

	const LOGNAME = 'PohodaApi';
	const PARAM_FILE = 'file';

	/** @var IRequest @inject */
	public $httpRequest;

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionReadShortStock()
	{
		$this->resource->message = 'actionReadShortStock ' . rand(1, 100);
	}

	public function actionReadOrders()
	{
		$this->setView();
	}

	public function actionCreateStore($use_gzip_upload)
	{
		try {
			$xml = $this->getFileContent($use_gzip_upload);
			$this->pohodaFacade->recieveXml($xml, $this->action);
			$this->resource->state = 'ok';
		} catch (Exception $ex) {
			$this->resource->state = 'error';
			$this->resource->message = $ex->getMessage();
			Debugger::log($ex->getMessage(), self::LOGNAME);
		}
		$this->setView('state');
	}

	public function actionCreateShortStock($use_gzip_upload)
	{
		try {
			$xml = $this->getFileContent($use_gzip_upload);
			$this->pohodaFacade->recieveXml($xml, $this->action);
			$this->resource->state = 'ok';
		} catch (Exception $ex) {
			$this->resource->state = 'error';
			$this->resource->message = $ex->getMessage();
			Debugger::log($ex->getMessage(), self::LOGNAME);
		}
		$this->setView('state');
	}

	private function getFileContent($gzip)
	{
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
