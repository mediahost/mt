<?php

namespace App\ApiModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\Parameter;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Facade\StockFacade;
use App\Model\Repository\StockRepository;
use Nette\Caching\Cache;

class ExportProductsPresenter extends BasePresenter
{

	const KEY_ALL_STOCKS = 'heureka-stocks';
	const TAG_ALL_STOCKS = 'heureka/stocks';
	const TAG_STOCK = 'heureka/stock';
	const TAG_DELIVERY = 'heureka/delivery';

	/** @var StockFacade @inject */
	public $stockFacade;

	public function actionReadHeureka()
	{
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

			$list = new ProductList();
			$list->setTranslator($this->translator);
			$list->setExchange($this->exchange, $this->exchange->getDefault());
			$list->qb = $stockRepo->createQueryBuilder('s')
					->innerJoin('s.product', 'p');
			$list->showOnlyAvailable = $this->settings->modules->heureka->onlyInStore;

			$paramRepo = $this->em->getRepository(Parameter::getClassName());
			$allParams = $paramRepo->findAll();

			$paymentRepo = $this->em->getRepository(Payment::getClassName());
			$paymentOnDelivery = $paymentRepo->find(Payment::ON_DELIVERY);
			$shippingRepo = $this->em->getRepository(Shipping::getClassName());
			$shippings = $shippingRepo->findBy([
				'active' => TRUE,
				'needAddress' => TRUE,
			]);
			
			$locale = $this->translator->getLocale();
			$cache = $this->stockFacade->getCache();
			
			$stocks = $cache->load(self::KEY_ALL_STOCKS . $locale);
			if (!$stocks) {
				$stocks = $list->getData(FALSE, TRUE, TRUE, FALSE);
				$cache->save(self::KEY_ALL_STOCKS . $locale, $stocks, [
					Cache::TAGS => [self::TAG_STOCK],
				]);
			}

			$this->template->stocks = $stocks;
			$this->template->params = $allParams;
			$this->template->shippings = $shippings;
			$this->template->paymentOnDelivery = $paymentOnDelivery;
			$this->template->locale = $locale;
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
