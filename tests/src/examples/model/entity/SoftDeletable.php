<?php

namespace Test\Examples\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity
 * https://github.com/Zenify/DoctrineBehaviors
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/DeletableEntity.php
 *
 * @property string $name
 * @property DateTime $deletedAt
 */
class SoftDeletable extends BaseEntity
{
	
	use Identifier;
	use \Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;

	public function __toString()
	{
		return (string) $this->name;
	}

}
