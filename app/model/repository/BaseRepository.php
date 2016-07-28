<?php

namespace App\Model\Repository;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryException;
use LogicException;
use Tracy\Debugger;

abstract class BaseRepository extends EntityRepository implements IRepository
{

	const ALIAS = 'e';
	const CACHE_LIFETIME = 1209600; // 14 days
	const CRITERIA_ORX_KEY = 'orx';

	private $criteriaJoins = [];

	public function save($entity)
	{
		$this->_em->persist($entity);
		$this->_em->flush();
		return $entity;
	}

	public function delete($entity)
	{
		$this->_em->remove($entity);
		$this->_em->flush();
		return $entity;
	}

	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
	{
		if (array_key_exists(self::CRITERIA_ORX_KEY, $criteria)) {
			$criteriaOr = $criteria[self::CRITERIA_ORX_KEY];
			unset($criteria[self::CRITERIA_ORX_KEY]);
			return $this->findByWithOr($criteria, $criteriaOr, $orderBy, $limit, $offset);
		} else {
			return parent::findBy($criteria, $orderBy, $limit, $offset);
		}
	}

	public function countBy(array $criteria = array())
	{
		if (array_key_exists(self::CRITERIA_ORX_KEY, $criteria)) {
			$criteriaOr = $criteria[self::CRITERIA_ORX_KEY];
			unset($criteria[self::CRITERIA_ORX_KEY]);
			return $this->countByWithOr($criteria, $criteriaOr);
		} else {
			return parent::countBy($criteria);
		}
	}

	public function findPairs($criteria, $value = NULL, $orderBy = [], $key = NULL)
	{
		if (is_array($criteria) && array_key_exists(self::CRITERIA_ORX_KEY, $criteria)) {
			$criteriaOr = $criteria[self::CRITERIA_ORX_KEY];
			unset($criteria[self::CRITERIA_ORX_KEY]);
			return $this->findPairsWithOr($criteria, $criteriaOr, $value, $orderBy, $key);
		} else {
			return parent::findPairs($criteria, $value, $orderBy, $key);
		}
	}

	public function findByWithOr(array $criteria, array $criteriaOr, array $orderBy = null, $limit = null, $offset = null)
	{
		$qb = $this->createQueryBuilder(self::ALIAS)
			->whereCriteria($criteria);

		foreach ($criteriaOr as $orItem) {
			$this->appendAndOrCriteria($qb, $orItem[0], $orItem[1]);
		}

		$qb->autoJoinOrderBy((array)$orderBy);

		return $qb->getQuery()
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getResult();
	}

	public function countByWithOr(array $criteria, array $criteriaOr)
	{
		$qb = $this->createQueryBuilder(self::ALIAS)
			->whereCriteria($criteria);

		foreach ($criteriaOr as $orItem) {
			$this->appendAndOrCriteria($qb, $orItem[0], $orItem[1]);
		}

		return $qb->select('COUNT(' . self::ALIAS . ')')
			->setMaxResults(1)
			->getQuery()
			->getSingleScalarResult();
	}

	public function findPairsWithOr(array $criteria, array $criteriaOr, $value = NULL, $orderBy = [], $key = NULL)
	{
		if (!is_array($orderBy)) {
			$key = $orderBy;
			$orderBy = array();
		}
		if (empty($key)) {
			$key = $this->getClassMetadata()->getSingleIdentifierFieldName();
		}

		$qb = $this->createQueryBuilder(self::ALIAS)
			->whereCriteria($criteria);

		foreach ($criteriaOr as $orItem) {
			$this->appendAndOrCriteria($qb, $orItem[0], $orItem[1]);
		}

		$query = $qb
			->select(self::ALIAS . '.' . $value, self::ALIAS . '.' . $key)
			->resetDQLPart('from')->from($this->getEntityName(), self::ALIAS, self::ALIAS . '.' . $key)
			->autoJoinOrderBy((array)$orderBy)
			->getQuery();

		try {
			return array_map(function ($row) {
				return reset($row);
			}, $query->getArrayResult());
		} catch (\Doctrine\ORM\Query\QueryException $e) {
			throw new QueryException($e, $query);
		}
	}

	/**
	 * Gets the cache driver implementation that is used for query result caching.
	 * @return Cache|null
	 */
	public function getResultCacheDriver()
	{
		return $this->_em->getConfiguration()->getResultCacheImpl();
	}

	public function clearResultCache($id = NULL)
	{
		$resultCacheDriver = $this->getResultCacheDriver();
		if ($resultCacheDriver) {
			if ($id) {
				$resultCacheDriver->delete($id);
			} else {
				$resultCacheDriver->deleteAll();
			}
		}
	}

	public static function renameOrxWithAlias(Orx $withoutAlias, $alias)
	{
		$withAlias = new Orx();
		foreach ($withoutAlias->getParts() as $part) {
			if ($part instanceof Orx) {
				$aliasedPart = self::renameOrxWithAlias($part, $alias);
			} else if (!preg_match('/^\w+\.\w+/', $part)) {
				$aliasedPart = $alias . '.' . $part;
			}
			$withAlias->add($aliasedPart);
		}
		return $withAlias;
	}

	protected function appendAndOrCriteria(QueryBuilder &$qb, Orx $orx, array $params = [])
	{
		$orx = $this->renameExprWithJoin($qb, $orx);
		$qb->andWhere($orx);

		foreach ($params as $paramKey => $paramValue) {
			$qb->setParameter($paramKey, $paramValue);
		}
	}

	private function renameExprWithJoin(QueryBuilder &$qb, Composite $withoutAlias, $orx = TRUE)
	{
		$withAlias = $orx ? new Orx() : new Andx();
		foreach ($withoutAlias->getParts() as $part) {
			if ($part instanceof Orx || $part instanceof Andx) {
				$aliasedPart = $this->renameExprWithJoin($qb, $part, $part instanceof Orx);
			} else {
				$alias = $this->autoJoin($qb, $part);
				$aliasedPart = $alias . '.' . $part;
			}
			$withAlias->add($aliasedPart);
		}
		return $withAlias;
	}

	private function autoJoin(QueryBuilder &$qb, &$key)
	{
		$rootAliases = $qb->getRootAliases();
		$alias = reset($rootAliases);

		$pos = strpos($key, '.');
		$substring = substr($key, 0, $pos);
		if ($pos === FALSE || !in_array($substring, $rootAliases)) {
			$key = $alias . '.' . $key;
		}

		while (preg_match('~([^\\.]+)\\.(.+)~', $key, $m)) {
			$key = $m[2];
			$property = $m[1];

			if (in_array($property, $rootAliases)) {
				$alias = $property;
				continue;
			}

			if (isset($qb->criteriaJoins[$alias][$property])) {
				$alias = $qb->criteriaJoins[$alias][$property];
				continue;
			}

			$aliasLength = 1;
			do {
				$joinAs = substr($property, 0, $aliasLength++);
			} while (isset($qb->criteriaJoins[$joinAs]));
			$qb->criteriaJoins[$joinAs] = array();

			$qb->innerJoin($alias . '.' . $property, $joinAs);
			$qb->criteriaJoins[$alias][$property] = $joinAs;
			$alias = $joinAs;
		}

		return $alias;
	}

}

interface IRepository
{

	public function save($entity);

	public function delete($entity);
}

class RepositoryException extends LogicException
{

}
