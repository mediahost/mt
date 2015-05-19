<?php

namespace Test\Examples\Model\Entity\Asociation\OneToManyUnidirectionalWithJoinTable;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Inverzed
 * @ORM\Entity
 * 
 * @property string $number
 */
class Phonenumber extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $number;

}
