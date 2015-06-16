<?php

namespace App\Model\Repository;

use Doctrine\ORM\NoResultException;

class UnitRepository extends BaseRepository
{

	public function findOneByName($name)
	{
		$qb = $this->createQueryBuilder('u')
				->select('u, t')
				->join('u.translations', 't')
				->where('t.name = :name')
				->setParameter('name', $name);

		try {
			return $qb->setMaxResults(1)
							->getQuery()
							->getSingleResult();
		} catch (NoResultException $e) {
			return NULL;
		}
	}

}
