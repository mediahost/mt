<?php

namespace App\ApiModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\Category;
use App\Model\Entity\Parameter;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Facade\StockFacade;
use App\Model\Repository\StockRepository;
use Nette\Caching\Cache;

class ExportProductsPresenter extends BasePresenter
{

	/** @var StockFacade @inject */
	public $stockFacade;

	public function actionReadHeureka($reload = FALSE)
	{
		ini_set('max_execution_time', 1500);
		if ($reload) {
			proc_nice(19);
		}

		if (!$this->settings->modules->heureka->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else if (!in_array($this->translator->getLocale(), (array) $this->settings->modules->heureka->locales)) {
			$this->resource->state = 'error';
			$this->resource->message = 'This language is not supported';
		} else {
			switch ($this->translator->getLocale()) {
				case 'cs':
					$this->exchange->setWeb('CZK');
					break;
			}

			/* @var $stockRepo StockRepository */
			$stockRepo = $this->em->getRepository(Stock::getClassName());
			$categoryRepo = $this->em->getRepository(Category::getClassName());

			$showOnlyInStore = $this->settings->modules->heureka->onlyInStore;
			$denyCategory = NULL;
			if ($this->settings->modules->heureka->denyCategoryId) {
				$denyCategory = $categoryRepo->find($this->settings->modules->heureka->denyCategoryId);
			}

			$cacheKey = 'heureka-stocks-' . $this->translator->getLocale();
			$cacheTag = 'heureka/stocks/' . $this->translator->getLocale();

			if ($reload) {
				$cache = $this->stockFacade->getCache();
				$cache->clean([Cache::TAGS => [$cacheTag]]);
			}
			$stocks = $this->stockFacade->getExportStocksArray($showOnlyInStore, $denyCategory);

			$paymentRepo = $this->em->getRepository(Payment::getClassName());
			$paymentOnDelivery = $paymentRepo->find(Payment::ON_DELIVERY);
			$shippingRepo = $this->em->getRepository(Shipping::getClassName());
			$shippings = $shippingRepo->findBy([
				'active' => TRUE,
				'needAddress' => TRUE,
			]);

			$this->template->stocks = $stocks;
			$this->template->stockRepo = $stockRepo;
			$this->template->shippings = $shippings;
			$this->template->paymentOnDelivery = $paymentOnDelivery;
			$this->template->locale = $this->translator->getLocale();
			$this->template->defaultLocale = $this->translator->getDefaultLocale();
			$this->template->cacheKey = $cacheKey;
			$this->template->cacheTag = $cacheTag;
			$this->template->cpc = $this->settings->modules->heureka->cpc;
			$this->template->deliveryStoreTime = $this->settings->modules->heureka->deliveryStoreTime;
			$this->template->deliveryNotInStoreTime = $this->settings->modules->heureka->deliveryNotInStoreTime;
			$this->template->hideDelivery = $this->settings->modules->heureka->hideDelivery;
			$this->template->setTranslator($this->translator->domain('export.heureka'));

			$this->setView('heureka');
		}
	}

	public function actionReadZbozi()
	{
		$this->resource->message = 'Hello world';
	}

}
