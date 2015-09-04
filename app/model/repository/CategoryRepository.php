<?php

namespace App\Model\Repository;

use App\Model\Entity\Category;
use Doctrine\ORM\NoResultException;

class CategoryRepository extends BaseRepository
{

	const ALL_CATEGORIES_CACHE_ID = 'all-categories';

	/**
	 * @param string|array $url
	 * @param string $lang
	 * @return Category
	 */
	public function findOneByUrl($url, $lang = NULL)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);
		$parent = NULL;
		if (count($url)) {
			$parent = $this->findOneByUrl($url, $lang); // search only category with right parents
		}

		$qb = $this->createQueryBuilder('c')
				->join('c.translations', 't')
				->where('t.slug = :slug')
				->setParameter('slug', $slug);
		if ($parent) {
			$qb->andWhere('c.parent = :parent')
					->setParameter('parent', $parent);
		}
		if ($lang) {
			$qb->andWhere('t.locale = :lang')
					->setParameter('lang', $lang);
		}

		try {
			return $qb->setMaxResults(1)->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return NULL;
		}
	}

	/**
	 * @param string $name
	 * @param string $lang
	 * @return Category
	 */
	public function findOneByName($name, $lang = NULL)
	{
		$qb = $this->createQueryBuilder('c')
				->join('c.translations', 't')
				->where('t.name = :name')
				->setParameter('name', $name);
		if ($lang) {
			$qb->andWhere('t.locale = :lang')
					->setParameter('lang', $lang);
		}

		try {
			return $qb->setMaxResults(1)->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return NULL;
		}
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

	public function delete($entity, $deleteRecursive = TRUE)
	{
		if ($deleteRecursive && $entity->hasChildren) {
			foreach ($entity->children as $child) {
				$this->delete($child);
			}
		}
		return parent::delete($entity);
	}

}
