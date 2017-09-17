<?php

namespace App\AppModule\Presenters;

use App\Extensions\Foto;
use App\Extensions\FotoException;
use App\Extensions\GoogleTranslate;
use App\Extensions\Installer;
use App\Model\Entity\Group;
use App\Model\Entity\Order;
use App\Model\Entity\Product;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\Connection;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
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

	/** @var GoogleTranslate @inject */
	public $googleTranslate;

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
	 * @privilege('translateProducts')
	 */
	public function handleTranslateProducts()
	{
		$count = $this->translateProducts();
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

	private function translateProducts()
	{
		ini_set('max_execution_time', 200);

		$criteria = [
			'active' => TRUE,
			'deletedAt' => NULL,
			'translated' => FALSE,
		];

		$productRepo = $this->em->getRepository(Product::getClassName());
		$products = $productRepo->findBy($criteria, [
			'updatedAt' => 'ASC',
		], 10);

		$counter = 0;
		$defaultLocale = $this->translator->getDefaultLocale();
		foreach ($products as $product) {
			$product->setCurrentLocale($defaultLocale);
			$name = $product->name;
			$perex = $product->perex;
			$description = $product->description;
			$translated = TRUE;
			foreach ($this->translator->getAvailableLocales() as $localeCode) {
				$locale = substr($localeCode, 0, 2);
				if ($locale != $this->translator->getDefaultLocale()) {
					$translate = $product->translateAdd($locale);
					$translatedName = $this->googleTranslate->translate($name, $defaultLocale, $locale);
					$translatedPerex = $this->googleTranslate->translate($perex, $defaultLocale, $locale);
					$translatedDescription = $this->googleTranslate->translate($description, $defaultLocale, $locale);
					$translate->name = $translatedName;
					$translate->perex = $translatedPerex;
					$translate->description = $translatedDescription;
					if (!$translatedName) {
						$translated = FALSE;
					}
					$shortName = Strings::truncate($translatedName, 50);
					$shortPerex = Strings::truncate($translatedPerex, 50);
					$shortDescription = Strings::truncate($translatedDescription, 50);
					Debugger::log("PRODUCT ID:{$product->id};LANG:{$locale};TRANSLATED:{$translated};---{$shortName}---{$shortPerex}---{$shortDescription}", 'translations');
				}
			}
			$product->translated = $translated;
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

}
