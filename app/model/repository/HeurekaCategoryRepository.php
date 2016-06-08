<?php

namespace App\Model\Repository;

use Doctrine\ORM\AbstractQuery;
use Exception;
use Kdyby\Doctrine\QueryException;

class HeurekaCategoryRepository extends BaseRepository
{

	const ALL_CATEGORIES_CACHE_ID = 'all-heureka-categories_';

	public function findPairs($locale, $value = NULL, $orderBy = [], $key = NULL)
	{
		$criteria = [
			't.locale' => $locale,
		];

		if (!is_array($orderBy)) {
			$key = $orderBy;
			$orderBy = [];
		}

		if (empty($key)) {
			$key = $this->getClassMetadata()->getSingleIdentifierFieldName();
		}

		$query = $this->createQueryBuilder('c')
			->whereCriteria($criteria)
			->select($value, 'c.' . $key)
			->resetDQLPart('from')
			->from($this->getEntityName(), 'c', 'c.' . $key)
			->resetDQLPart('join')
			->join('c.translations', 't')
			->autoJoinOrderBy((array)$orderBy)
			->getQuery()
			->useResultCache(TRUE, self::CACHE_LIFETIME, self::ALL_CATEGORIES_CACHE_ID . $locale);

		try {
			return array_map(function ($row) {
				return reset($row);
			}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));

		} catch (Exception $e) {
			throw new QueryException($e, $query);
		}
	}

}
