<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Category;
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

	private function getSignedProducts($signId, $count = 10, $useCache = TRUE)
	{
		if (!$signId) {
			return [];
		}

		if ($useCache) {
			$cache = $this->getCache();
			$key = 'signed-Products-' . $signId . '-' . $this->translator->getLocale();
			$products = $cache->load($key);
			if (!$products) {
				$products = $this->getSignedProducts($signId, $count, FALSE);
				$cache->save($key, $products, array(
					Cache::EXPIRE => '1 day',
				));
			}
			return $products;
		}

		$newSign = $this->signRepo->find($signId);
		if (!$newSign) {
			return [];
		}
		$qb = $this->stockRepo
				->createQueryBuilder('s')
				->innerJoin('s.product', 'p')
				->innerJoin('p.signs', 'signs')
				->where('signs = :sign')
				->andWhere('s.active = :active AND p.active = :active')
				->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
				->setParameter('active', TRUE)
				->setParameter('sign', $newSign)
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

	public function urlToId($uri, Request $request)
	{
		$locale = $request->getParameter(RouterFactory::LOCALE_PARAM_NAME);
		$slugs = $this->getUrls($locale);
		$slug = array_search($uri, $slugs);
		if ($slug) {
			return $slug;
		}
		return NULL;
	}

	public function idToUrl($id, Request $request)
	{
		$locale = $request->getParameter(RouterFactory::LOCALE_PARAM_NAME);
		$slugs = $this->getUrls($locale);
		if (array_key_exists($id, $slugs)) {
			return $slugs[$id];
		}
		return NULL;
	}

	/** @return array */
	private function getUrls($locale = NULL)
	{
		if ($locale === NULL) {
			$locale = $this->translator->getDefaultLocale();
		}

		$cache = $this->getCache();
		$cacheKey = self::KEY_ALL_PRODUCTS_URLS . '_' . $locale;

		$urls[$locale] = $cache->load($cacheKey);
		if (!$urls[$locale]) {
			$urls[$locale] = $this->getLocaleUrlsArray($locale);
			$cache->save($cacheKey, $urls[$locale], [Cache::TAGS => [self::TAG_ALL_PRODUCTS]]);
		}

		return $urls[$locale];
	}

	/** @return array */
	private function getLocaleUrlsArray($locale)
	{
		$localeUrls = [];
		$this->categoryRepo->findAll(); // only for optimalization - doctrine use intern cache for objects
		$products = $this->productRepo->findAllWithTranslation(['active' => TRUE]);
		foreach ($products as $product) {
			$product->setCurrentLocale($locale);
			$localeUrls[$product->id] = $product->url;
		}
		return $localeUrls;
	}

	/** @return Cache */
	public function getCache()
	{
		$cache = new Cache($this->cacheStorage, get_class($this));
		return $cache;
	}

	public function getExportStocksArray($onlyInStore = TRUE, Category $denyCategory = NULL)
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
		if ($denyCategory) {
			$denyCategories = implode(',', array_keys($denyCategory->childrenArray));
			$qb
					->andWhere('p.mainCategory NOT IN (:categories)')
					->setParameter('categories', $denyCategories);
		}
		return $qb->getQuery()
						->getResult(AbstractQuery::HYDRATE_ARRAY);
	}

}
