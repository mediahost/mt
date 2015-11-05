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
use Nette\Utils\Image;
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

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionUpdateProducts($toUpdate = 0)
	{
		ini_set('max_execution_time', 300);
		Debugger::timer();
		$maxUpdates = 200;
		$updateCount = $toUpdate >= 0 ? ($toUpdate <= $maxUpdates ? $toUpdate : $maxUpdates) : 0;
		$updated = $this->actualizeProducts($updateCount);
		$time = Debugger::timer();
		$this->status = parent::STATUS_OK;
		$this->message = sprintf('%s producers was updated. (%f2)', $updated, $time);
	}

	public function actionUpdateCategories()
	{
		$added = $this->addCategories();
		$this->status = parent::STATUS_OK;
		$this->message = sprintf('%s categories was inserted.', $added);
	}

	public function actionUpdateProducers()
	{
		$added = $this->addProducers();
		$this->status = parent::STATUS_OK;
		$this->message = sprintf('%s producers was inserted.', $added);
	}

	private function actualizeProducts($updateCountMax = 0)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$vatRepo = $this->em->getRepository(Vat::getClassName());

		$products = $this->getJson(self::URL_PRODUCTS);
		$unit = $unitRepo->find(1);

		$stocks = $stockRepo->findBy([], ['updatedAt' => 'ASC'], $updateCountMax);

		$updated = 0;
		foreach ($stocks as $stock) {
			/* @var $stock Stock */
			if (isset($products->{$stock->id})) {
				$oldProduct = $products->{$stock->id};
			} else {
				$stock->active = FALSE;
				$this->em->persist($stock);
				Debugger::log('Product with ID ' . $stock->id . ' was deactivated', self::LOGNAME);
				continue;
			}

			$pohodaCode = $oldProduct->importedCode ? $oldProduct->importedCode : $oldProduct->code;
			switch ($oldProduct->importedFrom) {
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

			$productTranslation = $stock->product->translateAdd($this->locale);
			$productTranslation->name = $oldProduct->name;
			$productTranslation->description = $oldProduct->descript;
			$productTranslation->perex = $oldProduct->perex;
			$stock->product->mergeNewTranslations();
			$stock->product->unit = $unit;

			$stock->active = $oldProduct->active;
			$stock->pohodaCode = $pohodaCode;
			$stock->barcode = $oldProduct->ean;
			$stock->quantity = $oldProduct->quantity;
			$stock->lock = $oldProduct->lock;
			$stock->barcode = $oldProduct->ean;
			$stock->importedFrom = $importedFrom;

			$productVat = $oldProduct->vat > 0 ? $oldProduct->vat : 0;
			$vat = $vatRepo->findOneByValue($productVat);
			if (!$vat) {
				$vat = new Vat(NULL, $productVat);
				$this->em->persist($vat);
			}
			$stock->vat = $vat;
			$stock->purchasePrice = $oldProduct->purchasePrice;
			$stock->oldPrice = $oldProduct->oldPrice;

			if (!$stock->hasDiscounts()) {
				foreach ($oldProduct->priceLevelsVatNot as $levelId => $priceLevel) {
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
							$stock->addDiscount($discount, $group);
						}
					}
				}
			}
			$stock->setDefaltPrice($oldProduct->price, (bool) $oldProduct->vatIncluded);

			$producers = $this->getProducersNames();
			if ($oldProduct->producerId && array_key_exists($oldProduct->producerId, $producers)) {
				$producerName = $producers[$oldProduct->producerId];
				$stock->product->producer = $producerRepo->findOneByName($producerName);
			}

			$categories = $this->getCategoriesNames();
			if ($oldProduct->mainCategoryId && array_key_exists($oldProduct->mainCategoryId, $categories)) {
				$categoryName = $categories[$oldProduct->mainCategoryId];
				$mainCategory = $categoryRepo->findOneByName($categoryName, $this->locale);
				if ($mainCategory) {
					$stock->product->mainCategory = $mainCategory;
				}
			}
			if (!$stock->product->mainCategory) {
				Debugger::log('Missing mainCategory for CODE: ' . $pohodaCode . '; ID: ' . $oldProduct->id, self::LOGNAME);
				continue;
			}
			if (count($oldProduct->otherCategoriesIds) && count($stock->product->categories) === 1) {
				$stock->product->clearCategories();
				foreach ($oldProduct->otherCategoriesIds as $otherCategoryId) {
					if (array_key_exists($otherCategoryId, $categories)) {
						$categoryName = $categories[$otherCategoryId];
						$category = $categoryRepo->findOneByName($categoryName, $this->locale);
						if ($category) {
							$stock->product->addCategory($category);
						}
					}
				}
			}

			if ($oldProduct->image && !$stock->product->image) {
				$file = $this->downloadImage($oldProduct->image);
				$stock->product->image = $file;
			}
			if (count($oldProduct->otherImages) && !count($stock->product->images)) {
				foreach ($oldProduct->otherImages as $otherImage) {
					$file = $this->downloadImage($otherImage);
					$stock->product->otherImage = $file;
				}
			}

			$this->em->persist($stock);
			$updated++;

			if (($updated % 500) === 0) {
				$this->em->flush();
			}
		}
		$this->em->flush();
		return $updated;
	}

	private function downloadImage($url)
	{
		$content = file_get_contents($url);
		return Image::fromString($content);
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
