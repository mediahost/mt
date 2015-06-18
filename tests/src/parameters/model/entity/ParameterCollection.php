<?php

namespace Test\Parameters\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property ArrayCollection $parameters
 */
class ParameterCollection extends BaseEntity
{
	
	use Identifier;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;
	
	/** @ORM\OneToMany(targetEntity="Parameter", mappedBy="parent", cascade={"persist"}) */
	protected $parameters;
	
	public function __construct($name)
	{
		$this->name = $name;
		$this->parameters = new ArrayCollection();
		parent::__construct();
	}
	
	public function addParameter(ParameterName $paramName, $value)
	{
		$parameter = new Parameter();
		$parameter->parent = $this;
		$parameter->name = $paramName;
		$parameter->value = $value;
		$this->parameters->add($parameter);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
