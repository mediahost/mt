<?php

namespace App\Model\Repository;

use Kdyby\Doctrine\EntityRepository;
use LogicException;

abstract class BaseRepository extends EntityRepository implements IRepository
{

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

}

interface IRepository
{

	public function save($entity);

	public function delete($entity);
}

class RepositoryException extends LogicException
{

}