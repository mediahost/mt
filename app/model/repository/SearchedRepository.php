<?php

namespace App\Model\Repository;

class SearchedRepository extends BaseRepository
{

	public function findMostBy(array $criteria, $limit = null, $offset = null, $order = 'DESC')
	{
		$qb = $this->createQueryBuilder('s')
			->addSelect('COUNT(s.id) as counter')
			->groupBy('s.text')
			->whereCriteria($criteria)
			->orderBy('counter', $order);

		return $qb->getQuery()
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getResult();
	}

}
