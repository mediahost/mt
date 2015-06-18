<?php

namespace Test\Parameters\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 */
class ParameterName extends BaseEntity
{
	
	use Identifier;

	/** @ORM\Column(type="string", length=255) */
	protected $name;
	
	public function __construct($name)
	{
		$this->name = $name;
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
