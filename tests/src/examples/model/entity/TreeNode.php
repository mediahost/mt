<?php

namespace Test\Examples\Model\Entity;

use ArrayAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model\Tree;

/**
 * @ORM\Entity(repositoryClass="Test\Examples\Model\Repository\TreeNodeRepository")
 * https://github.com/Zenify/DoctrineBehaviors
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/TreeNodeEntity.php
 *
 * @property string $title
 */
class TreeNode extends BaseEntity implements Tree\NodeInterface, ArrayAccess
{

	const PATH_SEPARATOR = '/';

	use \Kdyby\Doctrine\Entities\Attributes\Identifier,
	 Tree\Node;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;

	public function __construct($id = null)
	{
		parent::__construct();
		$this->id = $id;
		$this->children = new ArrayCollection;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	/** {@inheritdoc} */
	public static function getMaterializedPathSeparator()
	{
		return self::PATH_SEPARATOR;
	}

}
