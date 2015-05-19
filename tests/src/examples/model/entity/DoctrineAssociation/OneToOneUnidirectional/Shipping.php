<?php

namespace Test\Examples\Model\Entity\Asociation\OneToOneUnidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Inverzed
 * @ORM\Entity
 * 
 * @property string $name
 */
class Shipping extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $name;

}
