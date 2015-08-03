<?php

namespace App\Model\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Func;

class PohodaItemRepository extends BaseRepository
{

	public function findArrGroupedBy(array $criteria, $limit = null, $offset = null)
	{
		$qb = $this->createQueryBuilder('p')
				->addSelect(new Func('SUM', 'p.count'))
				->whereCriteria($criteria)
				->groupBy('p.code');
		return $qb->getQuery()
						->setMaxResults($limit)
						->setFirstResult($offset)
						->getResult(AbstractQuery::HYDRATE_ARRAY);
	}

}
