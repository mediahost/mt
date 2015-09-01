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
	
	const ORDERED = 1;
	const EXPEDED = 2;
	const DONE = 3;
	const STORNO = 4;
	// lock types
	const LOCK_ORDER = 1;
	const LOCK_DONE = 2;
	const LOCK_STORNO = 3;

	use Identifier;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\Column(type="smallint") */
	protected $locking;
	
	public function __toString()
	{
		return (string) $this->name;
	}
	
	public function isLocking($type)
	{
		return $this->locking === $type;
	}

}
