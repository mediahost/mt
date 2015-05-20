<?php

namespace Test\Examples\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity
 * https://github.com/Zenify/DoctrineBehaviors
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/LoggableEntity.php
 *
 * @property string $name
 * @property array $roles
 * @property DateTime $date
 */
class Loggable extends BaseEntity
{

	use Identifier;
	use \Knp\DoctrineBehaviors\Model\Loggable\Loggable;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;

	/** @ORM\Column(type="array", nullable=true) */
	protected $roles;

	/** @ORM\Column(type="date", nullable=true) */
	protected $date;

	public function __toString()
	{
		return (string) $this->name;
	}

}
