<?php

namespace App\Model\Repository;

use App\Model\Entity\Category;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\QueryBuilder;

class CategoryRepository extends BaseRepository
{

	const ALL_CATEGORIES_CACHE_ID = 'all-categories';
	const ROOT_CATEGORIES_CACHE_ID = 'root-categories';
	const CATEGORY_CACHE_ID = 'category_';

	/**
	 * @param string|array $url
	 * @param string $locale
	 * @return Category
	 */
	public function findOneByUrl($url, $locale = NULL)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);
		$parent = NULL;
		if (count($url)) {
			$parent = $this->findOneByUrl($url, $locale); // search only category with right parents
		}

		$qb = $this->createQueryBuilder('c')
			->join('c.translations', 't')
			->where('t.slug = :slug')
			->setParameter('slug', $slug);
		if ($parent) {
			$qb->andWhere('c.parent = :parent')
				->setParameter('parent', $parent);
		}
		if ($locale) {
			$this->extendQbWhereLocale($qb, $locale);
		}

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	/**
	 * @param string $name
	 * @param string $locale
	 * @return Category
	 */
	public function findOneByName($name, $locale = NULL, $parent = NULL)
	{
		$qb = $this->createQueryBuilder('c')
			->join('c.translations', 't')
			->where('t.name = :name')
			->setParameter('name', $name);
		if ($parent) {
			$qb->andWhere('c.parent = :parent')
				->setParameter('parent', $parent);
		} else {
			$qb->andWhere('c.parent IS NULL');
		}
		if ($locale) {
			$this->extendQbWhereLocale($qb, $locale);
		}

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	public function findAll()
	{
		$qb = $this->createQueryBuilder('c')
			->select('c, ch, t')
			->leftJoin('c.children', 'ch')
			->join('c.translations', 't');

		return $qb->getQuery()
			->useResultCache(TRUE, self::CACHE_LIFETIME, self::ALL_CATEGORIES_CACHE_ID)
			->getResult();
	}

	public function findRootIds()
	{
		$qb = $this->createQueryBuilder('c')
			->select('c.id')
			->where('c.parent IS NULL');

		$result = $qb->getQuery()
			->useResultCache(TRUE, self::CACHE_LIFETIME, self::ROOT_CATEGORIES_CACHE_ID)
			->getResult(AbstractQuery::HYDRATE_ARRAY);

		return array_map(function ($row) {
			return reset($row);
		}, $result);
	}

	public function delete($entity, $deleteRecursive = TRUE)
	{
		if ($deleteRecursive && $entity->hasChildren) {
			foreach ($entity->children as $child) {
				$this->delete($child);
			}
		}
		return parent::delete($entity);
	}

	private function extendQbWhereLocale(QueryBuilder &$qb, $locale, $alias = 't')
	{
		if (is_string($locale)) {
			$qb->andWhere($alias . '.locale = :locale')
				->setParameter('locale', $locale);
		} else if (is_array($locale)) {
			$orExpr = new Orx();
			foreach ($locale as $key => $localeItem) {
				$idKey = 'locale' . $key;
				$orExpr->add($alias . '.locale = :' . $idKey);
				$qb->setParameter($idKey, $localeItem);
			}
			$qb->andWhere($orExpr);
		}
	}

}
