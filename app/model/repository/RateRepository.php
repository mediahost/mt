<?php

namespace App\Model\Repository;

use Doctrine\ORM\AbstractQuery;
use Exception;

class RateRepository extends BaseRepository
{

	const ALL_RATES_CACHE_ID = 'all-rates';

	public function findValuePairs(array $criteria = [], array $orderBy = [])
	{
		$value = 'value';
		$key = $this->getClassMetadata()->getSingleIdentifierFieldName();

		$query = $this->createQueryBuilder('e')
				->whereCriteria($criteria)
				->select("e.$value", "e.$key")
				->resetDQLPart('from')->from($this->getEntityName(), 'e', 'e.' . $key)
				->autoJoinOrderBy((array) $orderBy)
				->getQuery();
		$query->useResultCache(TRUE, self::CACHE_LIFETIME, self::ALL_RATES_CACHE_ID);

		try {
			return array_map(function ($row) {
				return reset($row);
			}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));
		} catch (Exception $e) {
			throw $this->handleException($e, $query);
		}
	}

}
