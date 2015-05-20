<?php

namespace Test\Examples\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity
 * https://github.com/Zenify/DoctrineBehaviors
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/SluggableEntity.php
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/SluggableMultiEntity.php
 *
 * @property string $name
 * @property string $slug
 * @property DateTime $date
 */
class Sluggable extends BaseEntity
{
	
	use Identifier;
	use \Knp\DoctrineBehaviors\Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;

	/** @ORM\Column(type="datetime") */
	protected $date;

	public function __construct()
	{
		parent::__construct();
		$this->date = (new DateTime())->modify('-1 year');
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	protected function getSluggableFields()
	{
		return ['name'];
	}

}
