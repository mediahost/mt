<?php

namespace App\Model\Repository;

use App\Model\Entity\PohodaItem;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Func;
use Exception;

class PohodaItemRepository extends BaseRepository
{

	const ALIAS = 'p';
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

	public function findArrBy(array $criteria, $limit = null, $offset = null)
	{
		$qb = $this->createQueryBuilder('p')
			->whereCriteria($criteria)
			->autoJoinOrderBy((array)['updatedAt' => 'DESC']);
		return $qb->getQuery()
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getResult(AbstractQuery::HYDRATE_ARRAY);
	}

	public function getSumCountGroupedBy($code)
	{
		$qb = $this->createQueryBuilder('p')
			->select(new Func('SUM', 'p.count'))
			->whereCriteria(['p.' . self::CODE => $code])
			->groupBy('p.' . self::CODE);

		return $qb->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
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
				/** @var PohodaItem $item */
				$item = $this->find($id);
				if ($item && $item->storage->allowed) {
					$items[] = $item;
				}
			}
		}
		return $items;
	}

	public function updateSkipped($code, $skipped = NULL)
	{
		return $this->updateSynchronized($code, NULL, $skipped);
	}

	public function updateSynchronized($code, $synchronized = NULL, $skipped = NULL)
	{
		$values = [];
		if ($synchronized !== NULL) {
			$values[self::ALIAS . '.synchronized'] = $synchronized;
		}
		if ($skipped !== NULL) {
			$values[self::ALIAS . '.skipped'] = $skipped;
		}
		if ($values) {
			$qb = $this->createQueryBuilder(self::ALIAS)
				->update()
				->where(self::ALIAS . '.code = :code')
				->setParameter('code', $code);
			foreach ($values as $key => $value) {
				$qb->set($key, $value);
			}
			return $qb->getQuery()->execute();
		}
		return NULL;
	}

}
