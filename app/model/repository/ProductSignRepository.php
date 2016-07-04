<?php

namespace App\Model\Repository;

class ProductSignRepository extends BaseRepository
{

	public function deleteBy(array $criteria)
	{
		return $this->createQueryBuilder('e')
			->whereCriteria($criteria)
			->delete($this->getEntityName(), 'e')
			->getQuery()
			->execute();
	}

}
