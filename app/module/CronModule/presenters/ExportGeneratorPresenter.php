<?php

namespace App\CronModule\Presenters;

use App\Extensions\FilesManager;
use App\Model\Entity\Category;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Facade\StockFacade;
use App\Model\Repository\StockRepository;
use Nette\Application\ForbiddenRequestException;

class ExportGeneratorPresenter extends BasePresenter
{

	const DIR_FOR_SAVE = 'files/exports';

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var FilesManager @inject */
	public $filesManager;

	public function actionDealerStocks()
	{
		proc_nice(19);
		ini_set('max_execution_time', 600);

		if (!$this->settings->modules->dealer->enabled) {
			throw new ForbiddenRequestException('Dealer module is not allowed');
		}

		$stocks = $this->stockFacade->getExportShortStocksArray();
		$stockRepo = $this->em->getRepository(Stock::getClassName());

		$this->template->stocks = $stocks;
		$this->template->stockRepo = $stockRepo;
		$this->template->locale = $this->translator->getLocale();
		$this->template->defaultLocale = $this->translator->getDefaultLocale();
		$this->template->setTranslator($this->translator->domain('export.dealer'));
		$this->setView();

		$output = (string) $this->template;
		$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_DEALER_STOCKS, $this->translator->getLocale());

		file_put_contents($filename, $output);

		$this->status = parent::STATUS_OK;
		$this->message = 'File was generated';
	}

	public function actionDealerCategories()
	{
		proc_nice(19);
		ini_set('max_execution_time', 600);

		if (!$this->settings->modules->dealer->enabled) {
			throw new ForbiddenRequestException('Dealer module is not allowed');
		}

		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$categories = $categoryRepo->findAll();

		$this->template->categories = $categories;
		$this->template->locale = $this->translator->getLocale();
		$this->setView();

		$output = (string) $this->template;
		$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_DEALER_CATEGORIES, $this->translator->getLocale());

		file_put_contents($filename, $output);

		$this->status = parent::STATUS_OK;
		$this->message = 'File was generated';
	}

	public function actionHeurekaStocks()
	{
		proc_nice(19);
		ini_set('max_execution_time', 1500);

		if (!$this->settings->modules->heureka->enabled) {
			throw new ForbiddenRequestException('Dealer module is not allowed');
		} else if (!in_array($this->translator->getLocale(), (array) $this->settings->modules->heureka->locales)) {
			throw new ForbiddenRequestException('This language is not supported');
		}

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
		$this->template->cpc = $this->settings->modules->heureka->cpc;
		$this->template->deliveryStoreTime = $this->settings->modules->heureka->deliveryStoreTime;
		$this->template->deliveryNotInStoreTime = $this->settings->modules->heureka->deliveryNotInStoreTime;
		$this->template->hideDelivery = $this->settings->modules->heureka->hideDelivery;
		$this->template->setTranslator($this->translator->domain('export.heureka'));
		$this->setView();

		$output = (string) $this->template;
		$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_HEUREKA_STOCKS, $this->translator->getLocale());

		file_put_contents($filename, $output);

		$this->status = parent::STATUS_OK;
		$this->message = 'File was generated';
	}

	public function setView($view = NULL)
	{
		$name = $this->getName();
		$presenter = substr($name, strrpos(':' . $name, ':'));
		$action = $view ? $view : $this->action;
		$templatePath = __DIR__ . "/../templates/{$presenter}/{$action}.latte";
		$this->template->setFile(realpath($templatePath));
	}

}