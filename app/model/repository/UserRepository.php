<?php

namespace App\Model\Repository;

use App\Model\Entity\User;
use Doctrine\ORM\Query\QueryException;
use Exception;

class UserRepository extends BaseRepository
{

	public function findPairsByRoleId($roleId, $value = NULL, $orderBy = [], $key = NULL)
	{
		if (!is_array($orderBy)) {
			$key = $orderBy;
			$orderBy = [];
		}

		if (empty($key)) {
			$key = $this->getClassMetadata()->getSingleIdentifierFieldName();
		}

		$query = $this->createQueryBuilder()
				->select("e.$value", "e.$key")
				->from($this->getEntityName(), 'e', 'e.' . $key)
				->innerJoin('e.roles', 'r')
				->where('r.id = :roleid')
				->setParameter('roleid', $roleId)
				->autoJoinOrderBy((array) $orderBy)
				->getQuery();

		try {
			$getFirst = function ($row) {
				return reset($row);
			};
			return array_map($getFirst, $query->getArrayResult());
		} catch (Exception $e) {
			throw new QueryException($e, $query);
		}
	}

	/**
	 * @deprecated Use UserFacade::delete() instead
	 * @param User $entity
	 */
	public function delete($entity)
	{
		throw new RepositoryException('Use App\Model\Facade\UserFacade::delete() instead.');
	}

}
