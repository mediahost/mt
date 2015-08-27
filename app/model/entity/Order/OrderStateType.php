<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property bool $isOk
 */
class OrderStateType extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\Column(type="boolean") */
	protected $isOk;
	
	public function __toString()
	{
		return (string) $this->name;
	}

}
