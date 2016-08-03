<?php

namespace App\Extensions\Products\Components;

use App\Model\Entity\Category;
use App\Model\Entity\Parameter;
use App\Model\Entity\Price;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Repository\BaseRepository;
use App\Model\Repository\CategoryRepository;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

class DataHolder extends Object
{

	const STOCK_ALIAS = 's';
	const PRODUCT_ALIAS = 'p';
	const DEFAULT_PRICE_LEVEL = 'defaultPrice';
	const ORDER_BY_PRICE = 'price';
	const ORDER_BY_NAME = 'name';
	const PREFIX_ACCESSORIES = 'num_';

	/** @var EntityManager @inject */
	public $em;

	/** @var StockRepository */
	private $stockRepo;

	/** @var ProductRepository */
	private $productRepo;

	/** @var CategoryRepository */
	private $categoryRepo;

	/** @var array */
	private $stocks;

	/** @var array */
	private $productIds;

	/** @var string */
	private $priceLevelName;

	/** @var int total count of items */
	private $count;

	/** @var int */
	private $limit;

	/** @var int */
	private $offset;

	// <editor-fold defaultstate="collapsed" desc="filters">

	/** @var bool */
	private $needJoin = FALSE;

	/** @var array */
	private $productCriteria = [];

	/** @var array */
	private $stockCriteria = [];

	/** @var array */
	private $parameters = [];

	/** @var bool */
	private $appliedPreFilters = FALSE;

	/** @var array */
	private $orderBy = [];

	// </editor-fold>

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
	}


	// <editor-fold defaultstate="collapsed" desc="public setters">

	public function setPriceLevel($level)
	{
		$allowedProperties = Stock::getPriceProperties();
		if (array_key_exists($level, $allowedProperties)) {
			$this->priceLevelName = $allowedProperties[$level];
		} else {
			$this->priceLevelName = self::DEFAULT_PRICE_LEVEL;
		}
	}

	public function setPaging($limit = NULL, $offset = NULL)
	{
		$this->limit = $limit;
		$this->offset = $offset;
	}

	public function setSorting($by, $dir = Criteria::ASC)
	{
		switch ($by) {
			case self::ORDER_BY_PRICE:
				$by = $this->priceLevelName;
				break;
			case self::ORDER_BY_NAME:
				$by = 'product.translations.name';
				break;
			default:
				return $this;
		}
		$dir = $dir === Criteria::DESC ? $dir : Criteria::ASC;
		$this->orderBy = [$by => $dir];
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="public getters">

	public function getStocks()
	{
		if (!$this->stocks) {
			$this->applyPreFilters();
			$this->applyPostFilters();

			try {
				$criteria = $this->getStockCriteria();
				$this->stocks = $this->stockRepo->findBy($criteria, $this->orderBy, $this->limit, $this->offset);
			} catch (DataHolderException $e) {
				$this->stocks = [];
			}
		}
		return $this->stocks;
	}

	public function getProductsIds($withoutPost = FALSE)
	{
		if (!$this->productIds) {
			$this->applyPreFilters();
			if (!$withoutPost) {
				$this->applyPostFilters();
			}

			try {
				$criteria = $this->getStockCriteria();
				if ($withoutPost && isset($criteria[StockRepository::CRITERIA_ORX_KEY])) { // without accessories filter
					foreach ($criteria[StockRepository::CRITERIA_ORX_KEY] as $key => $item) {
						foreach ($item[1] as $itemKey => $itemValue) {
							if (preg_match('/^' . preg_quote(self::PREFIX_ACCESSORIES, '/') . '/', $itemKey)) {
								unset($criteria[StockRepository::CRITERIA_ORX_KEY][$key]);
								break;
							}
						}
					}
				}
				$this->productIds = $this->stockRepo->findPairs($criteria, 'IDENTITY(product)', $this->orderBy);
			} catch (DataHolderException $e) {
				$this->productIds = [];
			}
		}
		return $this->productIds;
	}

	public function getCount()
	{
		if ($this->count === NULL) {
			$this->applyPreFilters();
			$this->applyPostFilters();

			try {
				$criteria = $this->getStockCriteria();
				$this->count = $this->stockRepo->countBy($criteria);
			} catch (DataHolderException $e) {
				$this->count = 0;
			}
		}
		return $this->count;
	}

	public function getLimitPrices()
	{
		$this->applyPreFilters();

		try {
			$criteria = $this->getStockCriteria();
			list($lowerPrice, $higherPrice) = $this->stockRepo->getLimitPricesBy($criteria, $this->priceLevelName);
		} catch (DataHolderException $e) {
			$higherPrice = $lowerPrice = 0;
		}

		return [$lowerPrice, $higherPrice];
	}

	// </editor-fold>

	private function addStockCriteria($key, $value, $productAlias = FALSE)
	{
		switch ($key) {
			case 'product IN':
				if (array_key_exists($key, $this->stockCriteria)) {
					$this->stockCriteria[$key] = array_intersect($this->stockCriteria[$key], $value);
				} else {
					$this->stockCriteria[$key] = $value;
				}
				break;
			default:
				if ($key instanceof Orx) {
					if ($productAlias) {
						$key = BaseRepository::renameOrxWithAlias($key, 'product');
					}
					$this->stockCriteria[StockRepository::CRITERIA_ORX_KEY][] = [$key, $value];
				} else {
					$this->stockCriteria[$productAlias ? 'product.' . $key : $key] = $value;
				}
				if (preg_match('/^product\./', $key)) {
					$this->needJoin = TRUE;
				}
				break;
		}
		return $this;
	}

	private function addProductCriteria($key, $value)
	{
		if ($key instanceof Orx) {
			$this->productCriteria[ProductRepository::CRITERIA_ORX_KEY][] = [$key, $value];
		} else {
			$this->productCriteria[$key] = $value;
		}
		return $this;
	}

	private function isJoinBetter()
	{
		$better = [
			'active',
			'deleteDate',
			'inStore',
			'producer',
			'producerLine',
			'producerModel',
		];
		foreach ($this->productCriteria as $key => $value) {
			if ($key === ProductRepository::CRITERIA_ORX_KEY) {
				foreach ($value as $item) {
					foreach ($item[1] as $itemKey => $itemValue) {
						if (preg_match('/^num_/', $itemKey)) {
							continue;
						} else if (!in_array($itemKey, $better)) {
							return FALSE;
						}
					}
				}
			} else if (preg_match('/^parameter/', $key)) {
				continue;
			} else if (!in_array($key, $better)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	private function getStockCriteria()
	{
		if ($this->needJoin || $this->isJoinBetter()) {
			foreach ($this->productCriteria as $key => $value) {
				if ($key === ProductRepository::CRITERIA_ORX_KEY) {
					foreach ($value as $item) {
						$this->addStockCriteria($item[0], $item[1], TRUE);
					}
				} else {
					$this->addStockCriteria($key, $value, TRUE);
				}
			}
		} else {
			$productIds = $this->productRepo->findPairs($this->productCriteria, 'id');
			$this->addStockCriteria('product IN', $productIds);
		}
		$this->productCriteria = [];

		if (array_key_exists('product IN', $this->stockCriteria) && !count($this->stockCriteria['product IN'])) {
			throw new DataHolderException();
		}
		return $this->stockCriteria;
	}

	private function applyPreFilters()
	{
		if (!$this->appliedPreFilters) {
			$this->filterNotDeleted();
			$this->filterOnlyActive();
		}
		$this->appliedPreFilters = TRUE;

		return $this;
	}

	private function applyPostFilters()
	{
		$this->filterParameters();

		return $this;
	}

	// <editor-fold defaultstate="collapsed" desc="add filters">

	private function filterNotDeleted()
	{
		$or = new Orx([
			'deletedAt IS NULL',
			'deletedAt > :deleteDate'
		]);
		$assoc = [
			'deleteDate' => new DateTime(),
		];
		$this->addProductCriteria($or, $assoc);
		$this->addStockCriteria($or, $assoc);
		return $this;
	}

	private function filterOnlyActive()
	{
		$this->addProductCriteria('active', TRUE);
		$this->addStockCriteria('active', TRUE);
		return $this;
	}

	public function filterInStore($isInStore)
	{
		if ($isInStore) {
			$this->addStockCriteria('inStore >=', 1);
		}
		return $this;
	}

	public function filterCategory(Category $category)
	{
		$ids = array_keys($category->getChildrenArray());
		$productIds = $this->productRepo->getIdsByCategoryIds($ids);
		$this->addStockCriteria('product IN', $productIds);
		return $this;
	}

	public function filterAccessoriesFor(array $items)
	{
		$producerIds = [];
		$lineIds = [];
		$modelIds = [];
		foreach ($items as $item) {
			if ($item instanceof Producer) {
				$producerIds[] = $item->id;
			} elseif ($item instanceof ProducerLine) {
				$lineIds[] = $item->id;
				$producerIds[] = $item->producer->id;
			} elseif ($item instanceof ProducerModel) {
				$modelIds[] = $item->id;
				$lineIds[] = $item->line->id;
				$producerIds[] = $item->line->producer->id;
			}
		}

		$columns = [
			'accessoriesProducerIds' => $producerIds,
			'accessoriesLineIds' => $lineIds,
			'accessoriesModelIds' => $modelIds,
		];

		foreach ($columns as $columnName => $ids) {
			$assoc = [];
			$orx = new Orx();
			foreach ($ids as $key => $id) {
				$prefix = self::PREFIX_ACCESSORIES . crc32($columnName) . '_' . $key;
				$prefixA = $prefix . 'A';
				$prefixB = $prefix . 'B';
				$prefixC = $prefix . 'C';
				$prefixD = $prefix . 'D';
				$or = (new Orx($columnName . ' = :' . $prefixA))
					->add($columnName . ' LIKE :' . $prefixB)
					->add($columnName . ' LIKE :' . $prefixC)
					->add($columnName . ' LIKE :' . $prefixD);
				$orx->add($or);
				$assoc[$prefixA] = $id;
				$assoc[$prefixB] = "{$id},%";
				$assoc[$prefixC] = "%,{$id}";
				$assoc[$prefixD] = "%,{$id},%";
			}
			if ($orx->count()) {
				$this->addProductCriteria($orx, $assoc);
			}
		}

	}

	public function filterProducer($item)
	{
		$producerId = NULL;
		$lineId = NULL;
		$modelId = NULL;
		if ($item instanceof Producer) {
			$producerId = $item->id;
		} elseif ($item instanceof ProducerLine) {
			$lineId = $item->id;
			$producerId = $item->producer->id;
		} elseif ($item instanceof ProducerModel) {
			$modelId = $item->id;
			$lineId = $item->line->id;
			$producerId = $item->line->producer->id;
		}

		if ($producerId) {
			$this->addProductCriteria('producer', $producerId);
		}
		if ($lineId) {
			$this->addProductCriteria('producerLine', $lineId);
		}
		if ($modelId) {
			$this->addProductCriteria('producerModel', $modelId);
		}

		return $this;
	}

	public function filterFulltext($text)
	{
		$words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
		$productIds = $this->productRepo->getIdsByFulltext($words);
		$this->addStockCriteria('product IN', $productIds);
		return $this;
	}

	public function filterUpdatedFrom($time)
	{
		$dateTime = $time instanceof DateTime ? $time : DateTime::from($time);
		$orx = new Orx();
		$orx->add('product.updatedAt >= :updatedAt');
		$orx->add('updatedAt >= :updatedAt');
		$assoc = ['updatedAt' => $dateTime];
		$this->addStockCriteria($orx, $assoc);
		return $this;
	}

	public function filterCreatedFrom($time)
	{
		$dateTime = $time instanceof DateTime ? $time : DateTime::from($time);
		$this->addStockCriteria('product.createdAt >=', $dateTime);
		$this->addStockCriteria('createdAt >=', $dateTime);
		return $this;
	}

	public function filterPrice($lowPriceValue, $highPriceValue)
	{
		// prices are inserted with vat - recount price to price without vat
		$vatPricesLow = [];
		$vatPricesHigh = [];
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		foreach ($vatRepo->findAll() as $vat) {
			if ($lowPriceValue > 0) {
				$vatPricesLow[$vat->id] = new Price($vat, $lowPriceValue, FALSE);
			}
			if ($highPriceValue > 0) {
				$vatPricesHigh[$vat->id] = new Price($vat, $highPriceValue, FALSE);
			}
		}

		$params = [];
		if (count($vatPricesLow)) {
			$orx = new Orx();
			foreach ($vatPricesLow as $vatId => $price) {
				$andx = new Andx();
				$andx->add('vat = :vat' . $vatId);
				$andx->add($this->priceLevelName . ' >= :lowPrice' . $vatId);
				$orx->add($andx);
				$params['vat' . $vatId] = $vatId;
				$params['lowPrice' . $vatId] = $price->withoutVat;
			}
			$this->addStockCriteria($orx, $params);
		}

		$params = [];
		if (count($vatPricesHigh)) {
			$orx = new Orx();
			foreach ($vatPricesHigh as $vatId => $price) {
				$andx = new Andx();
				$andx->add('vat = :vat' . $vatId);
				$andx->add($this->priceLevelName . ' <= :highPrice' . $vatId);
				$orx->add($andx);
				$params['vat' . $vatId] = $vatId;
				$params['highPrice' . $vatId] = $price->withoutVat;
			}
			$this->addStockCriteria($orx, $params);
		}

		return $this;
	}

	public function filterParameter($code, $value)
	{
		$this->parameters[$code] = $value;
		return $this;
	}

	public function filterResetParameters()
	{
		$this->parameters = [];
		return $this;
	}

	private function filterParameters()
	{
		if (!count($this->parameters)) {
			return $this;
		}

		foreach ($this->parameters as $code => $value) {
			if (Parameter::checkCodeHasType($code, Parameter::STRING)) {
				$operator = 'LIKE';
			} else {
				$operator = '=';
			}
			$this->addProductCriteria("parameter{$code} {$operator}", $value);
		}

		return $this;
	}

	// </editor-fold>

}

class DataHolderException extends Exception
{

}

interface IDataHolderFactory
{

}
