<?php

namespace Test\Examples\Model\Entity\Asociation\ManyToManyUnidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Inverzed
 * @ORM\Entity
 * @ORM\Table(name="`group`")
 * 
 * @property string $name
 */
class Group extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $name;

}
