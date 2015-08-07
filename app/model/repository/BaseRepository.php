<?php

namespace App\Model\Repository;

use Doctrine\Common\Cache\Cache;
use Kdyby\Doctrine\EntityRepository;
use LogicException;

abstract class BaseRepository extends EntityRepository implements IRepository
{

	const CACHE_LIFETIME = 1209600; // 14 days

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

}

interface IRepository
{

	public function save($entity);

	public function delete($entity);
}

class RepositoryException extends LogicException
{
	
}
