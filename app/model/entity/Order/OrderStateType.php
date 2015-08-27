<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 */
class OrderStateType extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;
	
	public function __toString()
	{
		return (string) $this->name;
	}

}
