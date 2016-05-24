<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property DateTime $runTime
 */
class TodoTask extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @ORM\Column(type="string", length=256, nullable=false) */
	protected $name;

	/** @ORM\Column(type="datetime") */
	protected $runTime;

	public function __construct($name)
	{
		parent::__construct();
		$this->name = $name;
		$this->runTime = new DateTime();
	}

	public function isRunnable()
	{
		$now = new DateTime();
		return $now >= $this->runTime;
	}

}
