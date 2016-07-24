<?php

namespace App\Model\Repository;

use App\Model\Entity\Page;

class PageRepository extends BaseRepository
{

	const ALL_PAGES_CACHE_ID = 'all-pages_';

	public function findAllWithCache($locale)
	{
		$qb = $this->createQueryBuilder('p')
			->addSelect('t')
			->join('p.translations', 't');

		return $qb->getQuery()
			->useResultCache(TRUE, self::CACHE_LIFETIME, self::ALL_PAGES_CACHE_ID . $locale)
			->getResult();
	}

	/**
	 * @param string $url
	 * @param string $lang
	 * @return Page|NULL
	 */
	public function findOneByUrl($url, $lang = NULL)
	{
		$qb = $this->createQueryBuilder('p')
			->join('p.translations', 't')
			->where('t.slug = :slug')
			->setParameter('slug', $url);
		if ($lang) {
			$qb->andWhere('t.locale = :lang')
				->setParameter('lang', $lang);
		}

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

}
