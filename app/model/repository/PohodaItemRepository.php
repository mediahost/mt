<?php

namespace App\Model\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Func;
use Exception;

class PohodaItemRepository extends BaseRepository
{

	const CODE = 'code';

	private $codedIds;

	public function findArrGroupedBy(array $criteria, $limit = null, $offset = null)
	{
		$qb = $this->createQueryBuilder('p')
				->addSelect(new Func('SUM', 'p.count'))
				->whereCriteria($criteria)
				->groupBy('p.' . self::CODE);
		return $qb->getQuery()
						->setMaxResults($limit)
						->setFirstResult($offset)
						->getResult(AbstractQuery::HYDRATE_ARRAY);
	}

	public function findIdsForCode($useCache = TRUE)
	{
		if (is_array($this->codedIds) && count($this->codedIds) && $useCache) {
			return $this->codedIds;
		}

		$key = $this->getClassMetadata()->getSingleIdentifierFieldName();

		$query = $this->createQueryBuilder('p')
				->select('p.' . self::CODE, 'p.' . $key)
				->resetDQLPart('from')->from($this->getEntityName(), 'p', 'p.' . $key)
				->getQuery();

		$this->codedIds = [];

		try {
			$results = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
			foreach ($results as $result) {
				$codeValue = $result[self::CODE];
				$idValue = $result[$key];
				$this->codedIds[$codeValue][] = $idValue;
			}
		} catch (Exception $e) {
			throw $this->handleException($e, $query);
		}
		return $this->codedIds;
	}

	public function findByCodeAliasById($code)
	{
		$codes = $this->findIdsForCode();
		$items = [];
		if (array_key_exists($code, $codes)) {
			$ids = $codes[$code];
			foreach ($ids as $id) {
				$items[] = $this->find($id);
			}
		}
		return $items;
	}

}
