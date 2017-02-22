<?php

namespace App\AppModule\Presenters;

use App\Extensions\Foto;
use App\Extensions\FotoException;
use App\Extensions\Installer;
use App\Model\Entity\Group;
use App\Model\Entity\Order;
use App\Model\Entity\Product;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\Connection;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Tracy\Debugger;

class ServicePresenter extends BasePresenter
{

	const PRODUCT_UPDATE_CATEGORIES_IDS = 1;
	const PRODUCT_UPDATE_FULLTEXT = 2;

	/** @var Connection @inject */
	public $connection;

	/** @var Installer @inject */
	public $installer;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var Foto @inject */
	public $foto;

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('default')
	 */
	public function actionFixPrices()
	{
		$this->fixPrices();
		exit;
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$this->redirect('updates');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('tools')
	 */
	public function actionTools()
	{

	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('updates')
	 */
	public function actionUpdates()
	{

	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('creators')
	 */
	public function actionCreators()
	{

	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('thumbnails')
	 */
	public function actionThumbnails()
	{
		$this->template->allSizes = $this->foto->getThumbnailSizes();
		$this->template->folders = $this->foto->getFoldersInOriginal();
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('importInitData')
	 */
	public function handleImportInitData()
	{
		$this->reinstall();
		$this->importDbAll();
		$message = $this->translator->translate('Data was imported from SQL files');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('install')
	 */
	public function handleInstall()
	{
		$this->install();
		$message = $this->translator->translate('DB was instaled');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('reinstall')
	 */
	public function handleReinstall()
	{
		$this->reinstall();
		$message = $this->translator->translate('DB was reinstaled');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('removeCache')
	 */
	public function handleRemoveCache()
	{
		$this->removeCache();
		$message = $this->translator->translate('Cache was cleared');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('afterDeploy')
	 */
	public function handleAfterDeploy()
	{
		$this->removeCache();
		$message = $this->translator->translate('Cache was cleared with install');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('updateStocks')
	 */
	public function handleUpdateStocks()
	{
		$count = $this->updateStocks();
		$message = $this->translator->translate('%count% stocks was updated', $count);
		$this->flashMessage($message, 'success');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('updateProducts')
	 */
	public function handleUpdateProducts($method = self::PRODUCT_UPDATE_CATEGORIES_IDS)
	{
		$count = $this->updateProducts($method);
		$message = $this->translator->translate('%count% products was updated', $count);
		$this->flashMessage($message, 'success');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('updateOrders')
	 */
	public function handleUpdateOrders()
	{
		$count = $this->updateOrders();
		$message = $this->translator->translate('%count% orders was updated', $count);
		$this->flashMessage($message, 'success');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('resetBonusPrices')
	 */
	public function handleResetBonusPrices()
	{
		$this->resetBonusPrices();
		$message = $this->translator->translate('All bonus prices was restored to default values');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('resetBonusPrices')
	 */
	public function handleMakeThumbnails($size = NULL, $folder = NULL, $maximum = 500, $redirect = TRUE)
	{
		ini_set('max_execution_time', 300);
		try {
			$rest = $maximum;
			$this->foto->createThumbnails($size, $folder, $rest);
			$message = $this->translator->translate('All thumbnails was created');
			$this->flashMessage($message, 'success');
		} catch (FotoException $e) {
			$message = $this->translator->translate('Must run once again. Maximum allowed thumbnails (%count%) was created.', $maximum);
			$this->flashMessage($message, 'warning');
		}
		if ($redirect) {
			$this->redirect('this');
		}
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('clearThumbnailsSize')
	 */
	public function handleClearThumbnailsSize($size = NULL, $delete = FALSE)
	{
		try {
			$this->foto->clearFolder($size, $delete);
			$message = $this->translator->translate('Size %size% was deleted.', ['size' => $size]);
			$this->flashMessage($message, 'success');
		} catch (FotoException $e) {
			$message = $this->translator->translate('Size %size% was not deleted.', ['size' => $size]);
			$this->flashMessage($message, 'warning');
		}
		$this->redirect('this');
	}

	private function removeCache()
	{
		$cacheFolder = './../temp/cache/';

		foreach (scandir(realpath($cacheFolder)) as $key => $folder) {
			$folderName = realpath($cacheFolder . $folder);
			if ($folder !== '.' && $folder !== '..' && !preg_match('/^\.delete/', $folder)) {
				$newName = $cacheFolder . '/.delete' . uniqid() . $key;
				FileSystem::rename($folderName, $newName);
			}
		}

		foreach (scandir(realpath($cacheFolder)) as $folder) {
			$folderName = realpath($cacheFolder . $folder);
			if (preg_match('/^\.delete/', $folder)) {
				FileSystem::delete($folderName);
			}
		}

		return $this;
	}

	private function reinstall()
	{
		$this->uninstall();
		$this->install();
		return $this;
	}

	private function uninstall()
	{
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
		$this->em->clear();
		return $this;
	}

	private function install()
	{
		FileSystem::delete(realpath('./../temp/install/'));
		$this->installer
			->setInstallDoctrine(TRUE)
			->setInstallAdminer(FALSE)
			->setInstallComposer(FALSE);
		$this->installer->install();
		return $this;
	}

	private function importDbAll()
	{
		return $this;
	}

	private function updateStocks()
	{
		$criteria = [
			'active' => TRUE,
		];

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stocks = $stockRepo->findBy($criteria, [
			'updatedAt' => 'ASC',
		], 500);

		$counter = 0;
		foreach ($stocks as $stock) {
			/** @var Stock $stock */
			$stock->shopVariant = $this->shopVariant;
			$stock->recalculatePrices();
			$this->em->persist($stock);
			$counter++;
		}
		$this->em->flush();
		return $counter;
	}

	private function updateProducts($method)
	{
		switch ($method) {
			default:
			case self::PRODUCT_UPDATE_CATEGORIES_IDS:
				$criteria = [
					'categoriesIds' => NULL,
					'mainCategory NOT' => NULL,
				];
				$method = 'updateCategoriesForOptimized';
				break;
			case self::PRODUCT_UPDATE_FULLTEXT:
				$criteria = [
					'active' => TRUE,
					'fulltext' => NULL,
				];
				$method = 'updateFulltext';
				break;
		}

		$productRepo = $this->em->getRepository(Product::getClassName());
		$products = $productRepo->findBy($criteria, [
			'updatedAt' => 'ASC',
		], 500);

		$counter = 0;
		foreach ($products as $product) {
			call_user_func([$product, $method]);
			$this->em->persist($product);
			$counter++;
		}
		$this->em->flush();
		return $counter;
	}

	private function updateOrders()
	{
		$criteria = [
			'shop' => NULL,
		];

		$orderRepo = $this->em->getRepository(Order::getClassName());
		$orders = $orderRepo->findBy($criteria, [
			'updatedAt' => 'ASC',
		], 500);

		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());
		$shopVariant = $shopVariantRepo->find(1);

		$counter = 0;
		foreach ($orders as $order) {
			$order->shopVariant = $shopVariant;
			$this->em->persist($order);
			$counter++;
		}
		$this->em->flush();
		return $counter;
	}

	private function resetBonusPrices()
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$bonusGroups = $groupRepo->findByType(Group::TYPE_BONUS);

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stocks = $stockRepo->findByPrice11(0, [], 100);
		foreach ($stocks as $stock) {
			foreach ($bonusGroups as $group) {
				$stock->addDiscount($group->getDiscount(), $group);
			}
			$stock->recalculatePrices();
			$stockRepo->save($stock);
		}
		return $this;
	}

	private function fixPrices()
	{
		$repaired = 0;
		$stockRepo = $this->em->getRepository(Stock::getClassName());

		$criteria = [
			'active' => TRUE,
			'activeA' => TRUE,
			'updatedAt >= ' => new DateTime('now - 15 day'),
		];
		$orderBy = [
			'updatedAt' => 'ASC',
		];
		$stocks = $stockRepo->findBy($criteria, $orderBy, 9500, 0);
		Debugger::barDump(count($stocks));

		foreach ($stocks as $stock) {
			$wrongPrice = FALSE;

			$backupPrice = $this->findPrice($stock->id);
			if (!$backupPrice) {
				continue;
			} else {
				$backupPrice = (float)$backupPrice;
			}
			$stockPrice = $stock->getPrice()->withoutVat;

			$stockPriceEurOrig = $stockPrice / 27;
			$stockPriceEur = round($stockPriceEurOrig, 2);
			if ($stockPriceEur == $backupPrice) {
				$wrongPrice = TRUE;
			} else {
				$stockPriceEurPercent = $stockPriceEurOrig / 100;
				$tolerantPercents = 5;
				$tolerantValue = $tolerantPercents * $stockPriceEurPercent;
				$tolerantMinus = $stockPriceEurOrig - $tolerantValue;
				$tolerantPlus = $stockPriceEurOrig + $tolerantValue;
				if ($tolerantMinus <= $backupPrice && $backupPrice <= $tolerantPlus) {
					$wrongPrice = TRUE;
				}
			}

			$stockPricePercent = $stockPrice / 100;
			$diff = abs($stockPrice - $backupPrice);
			$percentageDiff = $diff / $stockPricePercent;
			if ($percentageDiff > 80) {
				$wrongPrice = TRUE;
			}

			if ($wrongPrice) {
				Debugger::barDump($stock->id);
				$logMess = 'ID: ' . $stock->id . '; '
					. 'Actual: ' . $stockPrice . '; '
					. 'Changed To: ' . $backupPrice;
				Debugger::barDump($logMess);
				exit;
				Debugger::log($logMess, 'price-revision');

				$stock->defaultPrice = $backupPrice;

				$stockRepo->save($stock);

				$repaired++;
			}
			if ($repaired > 0) {
				break;
			}
		}
		Debugger::barDump($repaired, 'repaired COUNT');
	}

	private function findPrice($id)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('default_price_a1', 'price');
		$sql = 'SELECT default_price_a1 FROM stock_20170220 WHERE id = ' . $id;
		$query = $stockRepo->createNativeQuery($sql, $rsm);

		$result = $query->getOneOrNullResult();
		if ($result) {
			return current($result);
		}
		return NULL;
	}

	private function findVatId($id)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('vat_a_id', 'vat');
		$sql = 'SELECT vat_a_id FROM stock_20170220 WHERE id = ' . $id;
		$query = $stockRepo->createNativeQuery($sql, $rsm);

		$result = $query->getOneOrNullResult();
		if ($result) {
			return current($result);
		}
		return NULL;
	}

}
