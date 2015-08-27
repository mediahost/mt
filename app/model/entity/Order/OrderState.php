<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property OrderStateType $type
 * @property string $name
 */
class OrderState extends BaseEntity
{

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="OrderStateType") */
	protected $type;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;
	
	public function __toString()
	{
		return (string) $this->name;
	}

}
