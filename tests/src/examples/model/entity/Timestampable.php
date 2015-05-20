<?php

namespace Test\Examples\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * https://github.com/Zenify/DoctrineBehaviors
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/TimestampableEntity.php
 *
 * @property string $name
 */
class Timestampable extends BaseEntity
{
	
	use Identifier;
	use \Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;

	public function __toString()
	{
		return (string) $this->name;
	}

}
