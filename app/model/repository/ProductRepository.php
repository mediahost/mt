<?php

namespace App\Model\Repository;

class ProductRepository extends BaseRepository
{

	/**
	 * @param string|array $url
	 * @param string $lang
	 * @return Product
	 */
	public function findOneByUrl($url, $lang = NULL)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);
		$category = NULL;
		if (count($url)) {
//			$category = $this->findOneByUrl($url, $lang);
		}

		$qb = $this->createQueryBuilder('p')
				->join('p.translations', 't')
				->where('t.slug = :slug')
				->setParameter('slug', $slug);
//		if ($category) {
//			$qb->andWhere('c.parent = :parent')
//					->setParameter('parent', $category);
//		}
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
