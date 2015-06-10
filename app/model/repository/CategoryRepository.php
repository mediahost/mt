<?php

namespace App\Model\Repository;

use App\Model\Entity\Category;
use Doctrine\ORM\NoResultException;

class CategoryRepository extends BaseRepository
{

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

}
