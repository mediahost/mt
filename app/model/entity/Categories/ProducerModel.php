<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property ProducerLine $line
 */
class ProducerModel extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\ManyToOne(targetEntity=ProducerLinee", inversedBy="models") */
	protected $line;

	public function __construct($name)
	{
		$this->name = $name;
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function getFullName($glue = ' / ')
	{
		return Helpers::concatStrings($glue, (string) $this->line->producer, (string) $this->line, (string) $this);
	}

}
