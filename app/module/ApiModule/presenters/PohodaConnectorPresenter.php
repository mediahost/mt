<?php

namespace App\ApiModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\PohodaItem;
use App\Model\Entity\Stock;
use App\Model\Facade\PohodaFacade;
use App\Model\Repository\PohodaItemRepository;
use App\Model\Repository\StockRepository;
use Exception;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class PohodaConnectorPresenter extends BasePresenter
{

	const LOGNAME = 'pohoda_api';
	const PARAM_FILE = 'file';

	/** @var IRequest @inject */
	public $httpRequest;

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionReadStorageCart()
	{
		if (!$this->settings->modules->pohoda->enabled || !$this->settings->modules->pohoda->allowedReadStorageCart) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {

			/* @var $stockRepo StockRepository */
			$stockRepo = $this->em->getRepository(Stock::getClassName());
			/* @var $pohodaRepo PohodaItemRepository */
			$pohodaRepo = $this->em->getRepository(PohodaItem::getClassName());

			$lastConvert = $this->pohodaFacade->getLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_CONVERT);

			$list = new ProductList();
			$list->setTranslator($this->translator);
			$list->setExchange($this->exchange, $this->exchange->getDefault());
			$list->qb = $stockRepo->createQueryBuilder('s')
					->innerJoin('s.product', 'p');
			$list->filter = [
				'updatedFrom' => $lastConvert,
			];

			$pohodaRepo->findAll(); // load all items in doctrine and find will be without SQL
//			$pohodaRepo->findByCode([380, 2222, 1111, 1456]);
			$this->template->stocks = $list->getData(FALSE);
//			$this->template->stocks = $list->getData();
			
			$this->template->pohodaRepo = $pohodaRepo;
			$this->template->ico = $this->settings->modules->pohoda->ico;
			$this->template->defaultStorage = $this->settings->modules->pohoda->defaultStorage;
			$this->template->typePrice = $this->settings->modules->pohoda->typePrice;
			$this->template->vatRates = $this->settings->modules->pohoda->vatRates;

			$this->pohodaFacade->setLastSync(PohodaFacade::SHORT_STOCK, PohodaFacade::LAST_DOWNLOAD);
			$this->setView('storageCart');
		}
	}

	public function actionReadOrders()
	{
		if (!$this->settings->modules->pohoda->enabled || !$this->settings->modules->pohoda->allowedReadOrders) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		}

		$this->pohodaFacade->setLastSync(PohodaFacade::ORDERS, PohodaFacade::LAST_DOWNLOAD);
		$this->resource->orders = rand(1, 100);
	}

	public function actionCreateStore($use_gzip_upload)
	{
		if (!$this->settings->modules->pohoda->enabled || !$this->settings->modules->pohoda->allowedCreateStore) {
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
		if (!$this->settings->modules->pohoda->enabled || !$this->settings->modules->pohoda->allowedCreateShortStock) {
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
