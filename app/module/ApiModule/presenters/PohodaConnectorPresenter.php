<?php

namespace App\ApiModule\Presenters;

use App\Extensions\Products\IProductListFactory;
use App\Extensions\Products\ProductList;
use App\Model\Entity\Order;
use App\Model\Entity\OrderStateType;
use App\Model\Entity\PohodaItem;
use App\Model\Entity\Stock;
use App\Model\Facade\PohodaFacade;
use App\Model\Repository\PohodaItemRepository;
use App\Model\Repository\StockRepository;
use Exception;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class PohodaConnectorPresenter extends BasePresenter
{

	const LOGNAME = 'pohoda_api';
	const PARAM_FILE = 'file';

	/** @var IRequest @inject */
	public $httpRequest;

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	/** @var IProductListFactory @inject */
	public $iProductListFactory;

	/** Priority CPU using */
	public function actionReadStorageCart()
	{
		proc_nice(19);

		if (!$this->settings->modules->pohoda->enabled || !$this->settings->modules->pohoda->allowedReadStorageCart) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {
			/* @var $pohodaRepo PohodaItemRepository */
			$pohodaRepo = $this->em->getRepository(PohodaItem::getClassName());

			$lastConvert = $this->pohodaFacade->getLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_CONVERT);

			$list = $this->iProductListFactory->create();
			$list->setTranslator($this->translator);
			$list->setExchange($this->exchange, $this->exchange->getDefault());
			$list->addFilterUpdatedFrom($lastConvert);

			$insertList = $this->iProductListFactory->create();
			$insertList->setTranslator($this->translator);
			$insertList->setExchange($this->exchange, $this->exchange->getDefault());
			$insertList->addFilterCreatedFrom(new DateTime('- 3 days'));

			$pohodaRepo->findAll(); // load all items in doctrine and find will be without SQL
			$this->template->stocks = $list->getData(FALSE);
			$this->template->stocksToInsert = $insertList->getData(FALSE);

			$this->template->pohodaRepo = $pohodaRepo;
			$this->template->ico = $this->settings->modules->pohoda->ico;
			$this->template->defaultStorage = $this->settings->modules->pohoda->defaultStorage;
			$this->template->typePrice = $this->settings->modules->pohoda->typePrice;
			$this->template->vatRates = $this->settings->modules->pohoda->vatRates;
			$this->template->lastEditTime = $lastConvert;
			$this->template->insertedStockIds = [];

			$this->pohodaFacade->setLastSync(PohodaFacade::SHORT_STOCK, PohodaFacade::LAST_DOWNLOAD);
			$this->setView('storageCart');
		}
	}

	/** Priority CPU using */
	public function actionReadOrders()
	{
		proc_nice(19);

		if (!$this->settings->modules->pohoda->enabled || !$this->settings->modules->pohoda->allowedReadOrders) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {

			$orderTypeRepo = $this->em->getRepository(OrderStateType::getClassName());
			$type = $orderTypeRepo->find(OrderStateType::STORNO);

			$orderRepo = $this->em->getRepository(Order::getClassName());
			$minusTime = '-' . $this->settings->modules->pohoda->ordersExportDaysBack;
			$lastEditTime = DateTime::from($minusTime);
			$conditions = [
				'createdAt >=' => $lastEditTime,
				'state.type !=' => $type,
			];
			$orders = $orderRepo->findBy($conditions, ['createdAt' => 'ASC']);

			$orderItems = [];
			foreach ($orders as $order) {
				foreach ($order->items as $item) {
					$orderItems[] = $item;
				}
			}

			$this->template->orders = $orders;
			$this->template->orderItems = $orderItems;
			$this->template->ico = $this->settings->modules->pohoda->ico;
			$this->template->defaultStorage = $this->settings->modules->pohoda->defaultStorage;
			$this->template->typePrice = $this->settings->modules->pohoda->typePrice;
			$this->template->vatRates = $this->settings->modules->pohoda->vatRates;
			$this->template->lastEditTime = $lastEditTime;
			$this->template->pageInfo = $this->settings->pageInfo;
			$this->template->homeCurrency = $this->exchange->getDefault()->getCode();
			$this->template->exchange = $this->exchange;
			$currency = $this->exchange[$this->exchange->getWeb()];
			$this->template->currencySymbol = $currency->getFormat()->getSymbol();

			$this->pohodaFacade->setLastSync(PohodaFacade::ORDERS, PohodaFacade::LAST_DOWNLOAD);
			$this->setView('orders');
		}
	}

	/** Priority CPU using */
	public function actionCreateStore($use_gzip_upload)
	{
		proc_nice(19);
		ini_set('max_execution_time', 120);

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

	/** Priority CPU using */
	public function actionCreateShortStock($use_gzip_upload)
	{
		proc_nice(19);
		ini_set('max_execution_time', 120);

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
