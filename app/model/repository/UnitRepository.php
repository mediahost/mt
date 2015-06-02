<?php

namespace App\Model\Repository;

class UnitRepository extends BaseRepository
{

	public function findByName($name)
	{
		$qb = $this->createQueryBuilder('u')
				->select('u, t')
				->join('u.translations', 't')
				->where('t.name = :name')
				->setParameter('name', $name);

		return $qb->getQuery()->getResult();
	}

}
