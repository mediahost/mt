<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Category;
use App\Model\Entity\Group;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Repository\CategoryRepository;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\SignRepository;
use App\Model\Repository\StockRepository;
use App\Router\RouterFactory;
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\Request;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;
use Nette\Utils\DateTime;

class StockFacade extends Object
{

	const KEY_ALL_PRODUCTS_URLS = 'product-urls';
	const TAG_ALL_PRODUCTS = 'all-products';
	const TAG_PRODUCT = 'product_';
	const TAG_STOCK = 'stock_';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var StockRepository */
	private $stockRepo;

	/** @var ProductRepository */
	private $productRepo;

	/** @var CategoryRepository */
	private $categoryRepo;

	/** @var SignRepository */
	private $signRepo;

	/** @var array */
	private $ids = [];

	/** @var array */
	private $urls = [];

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->signRepo = $this->em->getRepository(Sign::getClassName());
	}

	public function getLimitPrices($priceLevelName, Category $category = NULL, $producer = NULL)
	{
		$qb = $this->stockRepo->createQueryBuilder('s')
			->select("MIN(s.{$priceLevelName}) AS minimum, MAX(s.{$priceLevelName}) AS maximum")
			->innerJoin('s.product', 'p');

		if ($category) {
			$qb->innerJoin('p.categories', 'categories')
				->andWhere('categories IN (:categories)')
				->setParameter('categories', array_keys($category->childrenArray));
		}
		if ($producer instanceof Producer) {

		} else if ($producer instanceof ProducerLine) {

		} else if ($producer instanceof ProducerModel) {

		}
		$result = $qb->getQuery()->getOneOrNullResult();

		$limitPrices = [$result['minimum'], $result['maximum']];
		return $limitPrices;
	}

	private function getSignedProducts($signId, $count = 10)
	{
		if (!$signId) {
			return [];
		}

		$sign = $this->signRepo->find($signId);
		if (!$sign) {
			return [];
		}
		$qb = $this->stockRepo
			->createQueryBuilder('s')
			->innerJoin('s.product', 'p')
			->innerJoin('p.signs', 'signs')
			->where('signs.sign = :sign')
			->andWhere('s.active = :active AND p.active = :active')
			->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
			->andWhere('s.inStore >= 1')
			->setParameter('active', TRUE)
			->setParameter('sign', $sign)
			->setParameter('now', new DateTime());
		return $qb->orderBy('s.id', 'DESC')
			->setMaxResults($count)
			->getQuery()
			->getResult();
	}

	public function getSales($count = 5)
	{
		$signs = $this->settings->modules->signs;
		$id = $signs->enabled ? $signs->values->sale : NULL;
		return $this->getSignedProducts($id, $count);
	}

	public function getNews($count = 10)
	{
		$signs = $this->settings->modules->signs;
		$id = $signs->enabled ? $signs->values->new : NULL;
		return $this->getSignedProducts($id, $count);
	}

	public function getTops($count = 3)
	{
		$signs = $this->settings->modules->signs;
		$id = $signs->enabled ? $signs->values->top : NULL;
		return $this->getSignedProducts($id, $count);
	}

	public function getBestSellers()
	{
		return [];
	}

	public function urlToId($uri, Request $request = NULL, $locale = NULL, Product $product = NULL)
	{
		$locale = $this->getLocale($request, $locale);
		$hash = $this->createCacheHash($uri, $locale);

		if (!$product && isset($this->ids[$hash])) {
			return $this->ids[$hash];
		}

		$cache = $this->getCache();
		$id = $cache->load($hash);
		if (!$id) {
			$localeArr = $locale == $this->translator->getDefaultLocale() ? $locale : [$locale, $this->translator->getDefaultLocale()];
			$product = $product ? $product : $this->productRepo->findOneByUrl($uri, $localeArr);
			if ($product) {
				$id = $product->id;
				$categoryTags = $this->getCategoryTags($product->mainCategory);
				$this->ids[$hash] = $id;
				$cache->save($hash, $id, [Cache::TAGS => [
						self::TAG_ALL_PRODUCTS,
						self::TAG_PRODUCT . $id,
					] + $categoryTags]);
			}
		}
		return $id;
	}

	public function idToUrl($id, Request $request = NULL, $locale = NULL, Product $product = NULL)
	{
		$locale = $this->getLocale($request, $locale);
		$hash = $this->createCacheHash($id, $locale);

		if (!$product && isset($this->urls[$hash])) {
			return $this->urls[$hash];
		}

		$cache = $this->getCache();
		$url = $cache->load($hash);
		if (!$url) {
			$product = $product ? $product : $this->productRepo->find($id);
			if ($product) {
				$product->setCurrentLocale($locale);
				$url = $product->getUrl();
				$categoryTags = $this->getCategoryTags($product->mainCategory);

				$this->urls[$hash] = $url;
				$cache->save($hash, $url, [Cache::TAGS => [
						self::TAG_ALL_PRODUCTS,
						self::TAG_PRODUCT . $id,
					] + $categoryTags]);
			}
		}
		return $url;
	}

	/** @return Cache */
	public function getCache()
	{
		$cache = new Cache($this->cacheStorage, get_class($this));
		return $cache;
	}

	private function createCacheHash($value, $locale)
	{
		return md5($locale . $value);
	}

	private function getCategoryTags(Category $category)
	{
		$tags = [];
		foreach ($category->getPathWithThis() as $item) {
			$tags[] = CategoryFacade::TAG_CATEGORY . $item->id;
		}
		return $tags;
	}

	private function getLocale(Request $request = NULL, $locale = NULL)
	{
		$locale = $request ? $request->getParameter(RouterFactory::LOCALE_PARAM_NAME) : $locale;
		if (!$locale) {
			$locale = $this->translator->getDefaultLocale();
		}
		return $locale;
	}

	public function getExportStocksArray($onlyInStore = TRUE, Category $denyCategory = NULL, $limit = NULL)
	{
		$qb = $this->stockRepo->createQueryBuilder('s')
			->select('s, p, i, c, t, v')
			->innerJoin('s.product', 'p')
			->leftJoin('p.translations', 't')
			->innerJoin('p.image', 'i')
			->innerJoin('p.mainCategory', 'c')
			->innerJoin('s.vat', 'v')
			->andWhere('(t.locale = :lang OR t.locale = :defaultLang)')
			->setParameter('lang', $this->translator->getLocale())
			->setParameter('defaultLang', $this->translator->getDefaultLocale())
			->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
			->andWhere('p.deletedAt IS NULL OR p.deletedAt > :now')
			->setParameter('now', new DateTime())
			->andWhere('s.active = :active')
			->andWhere('p.active = :active')
			->setParameter('active', TRUE);
		if ($onlyInStore) {
			$qb
				->andWhere("s.inStore >= :inStore")
				->setParameter('inStore', 1);
		}
		if ($denyCategory && count($denyCategory->childrenArray)) {
			$qb
				->andWhere('p.mainCategory NOT IN (:categories)')
				->setParameter('categories', array_keys($denyCategory->childrenArray));
		}

		if ($limit) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()
			->getResult(AbstractQuery::HYDRATE_ARRAY);
	}

	public function getExportShortStocksArray($onlyInStore = TRUE, Category $denyCategory = NULL, $limit = NULL)
	{
		$qb = $this->stockRepo->createQueryBuilder('s')
			->select('s, p, i, c, t')
			->innerJoin('s.product', 'p')
			->leftJoin('p.translations', 't')
			->innerJoin('p.image', 'i')
			->innerJoin('p.mainCategory', 'c')
			->andWhere('(t.locale = :lang OR t.locale = :defaultLang)')
			->setParameter('lang', $this->translator->getLocale())
			->setParameter('defaultLang', $this->translator->getDefaultLocale())
			->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
			->andWhere('p.deletedAt IS NULL OR p.deletedAt > :now')
			->setParameter('now', new DateTime())
			->andWhere('s.active = :active')
			->andWhere('p.active = :active')
			->setParameter('active', TRUE);
		if ($onlyInStore) {
			$qb
				->andWhere("s.inStore >= :inStore")
				->setParameter('inStore', 1);
		}
		if ($denyCategory && count($denyCategory->childrenArray)) {
			$qb
				->andWhere('p.mainCategory NOT IN (:categories)')
				->setParameter('categories', array_keys($denyCategory->childrenArray));
		}

		if ($limit) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()
			->getResult(AbstractQuery::HYDRATE_ARRAY);
	}

	public function getExportStocksDetails($onlyInStore = TRUE, $limit = NULL)
	{
		$qb = $this->stockRepo->createQueryBuilder('s')
			->select('s')
			->innerJoin('s.product', 'p')
			->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
			->andWhere('p.deletedAt IS NULL OR p.deletedAt > :now')
			->setParameter('now', new DateTime())
			->andWhere('s.active = :active')
			->andWhere('p.active = :active')
			->setParameter('active', TRUE);
		if ($onlyInStore) {
			$qb
				->andWhere("s.inStore >= :inStore")
				->setParameter('inStore', 1);
		}

		if ($limit) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()
			->getResult(AbstractQuery::HYDRATE_OBJECT);
	}

	public function recountPrices($offset = 0, $limit = 500)
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findBy(['type' => Group::TYPE_BONUS]);

		$stocks = $this->stockRepo->findBy([], NULL, $limit, $offset);

		foreach ($stocks as $stock) {
			/* @var $stock Stock */
			foreach ($groups as $group) {
				$stock->addDiscount($group->getDiscount(), $group);
			}
			$this->em->persist($stock);
		}
		$this->em->flush();

		return $this;
	}

}
