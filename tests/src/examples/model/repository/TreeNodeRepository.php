<?php

namespace Test\Examples\Model\Repository;

use Doctrine\DBAL\LockMode;
use Kdyby\Doctrine\EntityRepository;
use Knp\DoctrineBehaviors\Model\Tree\NodeInterface;
use Knp\DoctrineBehaviors\ORM\Tree;
use Test\Examples\Model\Entity\TreeNode;

/**
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/TreeNodeEntityRepository.php
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/TreeNodeEntity.php
 */
class TreeNodeRepository extends EntityRepository
{

	use Tree\Tree;

	/**
	 * Saving entity
	 * @param TreeNode $entity
	 * @return TreeNode
	 */
	public function save(TreeNode $entity)
	{
		$this->_em->persist($entity);
		$this->_em->flush($entity);
		return $entity;
	}

	/**
	 * Returns an array of node hydrated with its children and parents
	 *
	 * @api
	 *
	 * @param string $path
	 * @param string $rootAlias
	 *
	 * @return NodeInterface[] an array of nodes
	 */
	public function getTrees($path = '', $rootAlias = 't')
	{
		$trees = [];
		$results = $this->getFlatTree($path, $rootAlias);

		for ($i = 0; $i < count($results); $i++) {
			if (empty($path) && $results[0]->isRootNode()) {
				$trees[$i] = $this->buildTree($results);
			}
			array_shift($results);
		}
		return $trees;
	}

	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
	{
		$this->getTrees();
		return parent::findBy($criteria, $orderBy, $limit, $offset);
	}

	public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
	{
		$this->getTrees();
		return parent::find($id, $lockMode, $lockVersion);
	}

}
