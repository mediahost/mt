<?php

namespace App\CronModule\Presenters;

use App\Model\Entity\Category;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Producer;
use App\Model\Entity\Stock;
use App\Model\Entity\Unit;
use App\Model\Entity\Vat;
use App\Model\Facade\PohodaFacade;
use App\Model\Repository\CategoryRepository;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Tracy\Debugger;

class MigrationPresenter extends BasePresenter
{

	const LOGNAME = 'migration_cron';
	const FROM_OLD_VERSION = 'mt-joomla';
	const FROM_FIRST_VERSION = NULL;
	const FROM_MOBILESHOP_API = 'mobileshop';
	const IMPORTED_FROM_OLD_VERSION = 'old-ver';
	const IMPORTED_FROM_FIRST_VERSION = 'first-ver';
	const IMPORTED_FROM_MOBILESHOP_API = 'mobileshop-api';

	/** @link http://www.mobilnetelefony.sk/export/migration/create-products */
	const URL_PRODUCTS = 'http://www.mobilnetelefony.sk/export/migration/get-products';

	/** @link http://www.mobilnetelefony.sk/export/migration/create-categories */
	const URL_CATEGORIES = 'http://www.mobilnetelefony.sk/export/migration/get-categories';

	/** @link http://www.mobilnetelefony.sk/export/migration/create-producers */
	const URL_PRODUCERS = 'http://www.mobilnetelefony.sk/export/migration/get-producers';

	/** @link http://www.mobilnetelefony.sk/export/migration/create-groups */
	const URL_GROUPS = 'http://www.mobilnetelefony.sk/export/migration/get-groups';

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionUpdateProducts()
	{
		list($inserted, $updated) = $this->actualizeProducts();
		$this->status = parent::STATUS_OK;
		$this->message = sprintf('%s producers was updated and %s was inserted.', $updated, $inserted);
	}

	private function actualizeProducts()
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$vatRepo = $this->em->getRepository(Vat::getClassName());

		$products = $this->getJson(self::URL_PRODUCTS);
		$unit = $unitRepo->find(1);

		$inserted = 0;
		$updated = 0;
		foreach ($products as $product) {
			if ($product->active) {

				$pohodaCode = $product->importedCode ? $product->importedCode : $product->code;
				switch ($product->importedFrom) {
					case self::FROM_OLD_VERSION:
						$importedFrom = self::IMPORTED_FROM_OLD_VERSION;
						break;
					case self::FROM_MOBILESHOP_API:
						$importedFrom = self::IMPORTED_FROM_MOBILESHOP_API;
						break;
					default:
						$importedFrom = self::IMPORTED_FROM_FIRST_VERSION;
						break;
				}

				/* @var $dbStock Stock */
				$dbStock = $stockRepo->findOneByPohodaCode($pohodaCode);
				if (!$dbStock) {
//					$dbStock = new Stock();
//					$dbStock->pohodaCode = $pohodaCode;
//					$inserted++;
					continue;
				} else {
					$updated++;
				}

				Debugger::barDump($pohodaCode);
				Debugger::barDump($product);

				$productTranslation = $dbStock->product->translateAdd($this->locale);
				$productTranslation->name = $product->name;
				$productTranslation->description = $product->descript;
				$productTranslation->perex = $product->perex;
				$dbStock->product->mergeNewTranslations();
				$dbStock->product->unit = $unit;

				$dbStock->active = TRUE;
				$dbStock->barcode = $product->ean;
				$dbStock->quantity = $product->quantity;
				$dbStock->lock = $product->lock;
				$dbStock->barcode = $product->ean;
				$dbStock->importedFrom = $importedFrom;

				$productVat = $product->vat > 0 ? $product->vat : 0;
				$vat = $vatRepo->findOneByValue($productVat);
				if (!$vat) {
					$vat = new Vat($productVat);
					$this->em->persist($vat);
				}
				$dbStock->vat = $vat;
				$dbStock->purchasePrice = $product->purchasePrice;
				$dbStock->oldPrice = $product->oldPrice;
				foreach ($product->priceLevelsVatNot as $levelId => $priceLevel) {
					switch ($levelId) {
						case 7:
							$groupId = 1;
							break;
						case 8:
							$groupId = 2;
							break;
					}
					$fixed = array_key_exists(0, $priceLevel) ? $priceLevel[0] : NULL;
					$percentage = array_key_exists(1, $priceLevel) ? $priceLevel[1] : NULL;
					if ($percentage) {
						$discount = new Discount(100 - $percentage, Discount::PERCENTAGE);
					} elseif ($fixed) {
						$discount = new Discount($fixed, Discount::FIXED_PRICE);
					}
					if (isset($discount) && isset($groupId)) {
						$group = $groupRepo->find($groupId);
						if ($group) {
							$dbStock->addDiscount($discount, $group);
						}
					}
				}
				$dbStock->setDefaltPrice($product->price, (bool) $product->vatIncluded);

				$producers = $this->getProducersNames();
				if ($product->producerId && array_key_exists($product->producerId, $producers)) {
					$producerName = $producers[$product->producerId];
					$dbStock->product->producer = $producerRepo->findOneByName($producerName);
				}
				$categories = $this->getCategoriesNames();
				$dbStock->product->clearCategories();
				if ($product->mainCategoryId && array_key_exists($product->mainCategoryId, $categories)) {
					$categoryName = $categories[$product->mainCategoryId];
					$mainCategory = $categoryRepo->findOneByName($categoryName, $this->locale);
					if ($mainCategory) {
						$dbStock->product->mainCategory = $mainCategory;
					}
				}
				if (!$dbStock->product->mainCategory) {
					Debugger::log('Missing mainCategory for CODE: ' . $pohodaCode . '; ID: ' . $product->id);
					continue;
				}
				foreach ($product->otherCategoriesIds as $otherCategoryId) {
					if (array_key_exists($otherCategoryId, $categories)) {
						$categoryName = $categories[$otherCategoryId];
						$category = $categoryRepo->findOneByName($producerName, $this->locale);
						if ($category) {
							$dbStock->product->addCategory($category);
						}
					}
				}

				if ($product->image) {
					$file = $this->downloadImage($product->image);
				}
				foreach ($product->otherImages as $otherImage) {
					$file = $this->downloadImage($otherImage);
				}

				$this->em->persist($dbStock);
				if (($inserted + $updated) % 500 === 0) {
					$this->em->flush();
				}
			}
		}
		$this->em->flush();
		return [$inserted, $updated];
	}
	
	private function downloadImage($url)
	{
//		Debugger::barDump($url);
		$file = file_get_contents($url);
		return;
	}

	public function actionUpdateProducers()
	{
		$added = $this->addProducers();
		$this->status = parent::STATUS_OK;
		$this->message = sprintf('%s producers was inserted.', $added);
	}

	private function addProducers()
	{
		$producers = $this->getJson(self::URL_PRODUCERS);

		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$added = 0;
		foreach ($producers as $producer) {
			if ($producer->active && !$producer->parentId) {
				$finded = $producerRepo->findOneBy(['name' => $producer->name]);
				if (!$finded) {
					$newProducer = new Producer($producer->name);
					$this->em->persist($newProducer);
					$added++;
				}
			}
		}
		$this->em->flush();
		return $added;
	}

	private function getProducersNames()
	{
		$names = [];
		$producers = $this->getJson(self::URL_PRODUCERS);
		foreach ($producers as $id => $producer) {
			$names[$id] = $producer->name;
		}
		return $names;
	}

	public function actionUpdateCategories()
	{
		$added = $this->addCategories();
		$this->status = parent::STATUS_OK;
		$this->message = sprintf('%s categories was inserted.', $added);
	}

	private function addCategories()
	{
		$categories = $this->getJson(self::URL_CATEGORIES);
		$categoryTree = $this->loadCategoryTree($categories);
		$added = 0;
		foreach ($categoryTree as $category) {
			$this->addCategory($category, NULL, $added);
		}
		return $added;
	}

	private function addCategory($categoryItem, $parent = NULL, &$added = 0)
	{
		/* @var $categoryRepo CategoryRepository */
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$category = $categoryItem->category;
		$children = $categoryItem->children;
		if ($category->active) {
			$dbCategory = $categoryRepo->findOneByName($category->name, $this->locale);
			if (!$dbCategory) {
				$dbCategory = new Category($category->name, $this->locale);
				$dbCategory->parent = $parent;
				$dbCategory->mergeNewTranslations();
				$categoryRepo->save($dbCategory);
				$added++;
			}
			foreach ($children as $child) {
				$this->addCategory($child, $dbCategory, $added);
			}
		}
	}

	private function loadCategoryTree($categories, $parentId = 0)
	{
		$children = [];
		foreach ($categories as $id => $category) {
			if ((int) $category->parentId === (int) $parentId) {
				$child = new ArrayHash();
				$child->category = $category;
				$child->children = $this->loadCategoryTree($categories, $id);
				$children[$id] = $child;
			}
		}
		return $children;
	}

	private function getCategoriesNames()
	{
		$names = [];
		$categories = $this->getJson(self::URL_CATEGORIES);
		foreach ($categories as $id => $category) {
			$names[$id] = $category->name;
		}
		return $names;
	}

	private function getJson($url)
	{
		$json = file_get_contents($url);
		return Json::decode($json);
	}

}
