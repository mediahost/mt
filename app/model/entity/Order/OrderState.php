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
	
	const ORDERED_IN_SYSTEM = 1;
	const IN_PROCEEDINGS = 2;
	const SENT_SHIPPERS = 3;
	const READY_TO_TAKE_EXPEDED = 4;
	const OK_RECIEVED = 5;
	const OK_TAKEN = 6;
	const CANCELED = 7;
	const READY_TO_TAKE = 8;
	const NO_STATE = self::CANCELED;

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="OrderStateType") */
	protected $type;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;
	
	public function __toString()
	{
		return (string) $this->id;
	}

}
