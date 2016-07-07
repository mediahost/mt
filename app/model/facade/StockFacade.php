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
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Object;
use Nette\Utils\DateTime;

class StockFacade extends Object
{

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

	public function getExportStocksArray($onlyInStore = TRUE, Category $denyCategory = NULL, $limit = NULL, $withHeureka = FALSE)
	{
		$qb = $this->stockRepo->createQueryBuilder('s')
			->select('v')
			->addSelect('partial s.{id, barcode, defaultPrice, inStore}')
			->addSelect('partial t.{id, name, description, locale}')
			->addSelect('partial i.{id, filename}')
			->addSelect('partial p.{id}')
			->innerJoin('s.product', 'p')
			->leftJoin('p.translations', 't')
			->innerJoin('p.image', 'i')
			->innerJoin('s.vat', 'v')
			->andWhere('(t.locale = :locale OR t.locale = :defaultLocale)')
			->setParameter('locale', $this->translator->getLocale())
			->setParameter('defaultLocale', $this->translator->getDefaultLocale())
			->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
			->andWhere('p.deletedAt IS NULL OR p.deletedAt > :now')
			->setParameter('now', new DateTime())
			->andWhere('s.active = :active')
			->andWhere('p.active = :active')
			->setParameter('active', TRUE);

		if ($withHeureka) {
			$qb->addSelect('partial c.{id}')
				->addSelect('IDENTITY(p.heurekaCategory) as p_heurekaCategoryId')
				->addSelect('IDENTITY(c.heurekaCategory) as c_heurekaCategoryId')
				->innerJoin('p.mainCategory', 'c');
		}
		
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
			->addSelect('partial s.{id, barcode, defaultPrice, inStore}')
			->addSelect('partial t.{id, name, description, slug, locale}')
			->addSelect('partial i.{id, filename}')
			->addSelect('partial p.{id}')
			->addSelect('IDENTITY(p.mainCategory) as mainCategoryId')
			->innerJoin('s.product', 'p')
			->leftJoin('p.translations', 't')
			->innerJoin('p.image', 'i')
			->andWhere('(t.locale = :locale OR t.locale = :defaultLocale)')
			->setParameter('locale', $this->translator->getLocale())
			->setParameter('defaultLocale', $this->translator->getDefaultLocale())
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
