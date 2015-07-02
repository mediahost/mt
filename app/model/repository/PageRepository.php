<?php

namespace App\Model\Repository;

use App\Model\Entity\Page;
use Doctrine\ORM\NoResultException;

class PageRepository extends BaseRepository
{

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

		try {
			return $qb->setMaxResults(1)->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return NULL;
		}
	}

}
