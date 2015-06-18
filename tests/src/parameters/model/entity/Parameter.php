<?php

namespace Test\Parameters\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property int|bool|string $value
 * @property ParameterName $name
 * @property ParameterCollection $parent
 */
class Parameter extends BaseEntity
{
	
	use Identifier;
	
    /** @ORM\ManyToOne(targetEntity="ParameterName") */
    protected $name;
	
    /** @ORM\ManyToOne(targetEntity="ParameterCollection", inversedBy="parameters") */
    protected $parent;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	private $string;

	/** @ORM\Column(type="integer", nullable=true) */
	private $number;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $bool;
	
	public function setValue($value)
	{
		$this->reset();
		if (is_int($value)) {
			$this->number = $value;
		} else if (is_bool($value)) {
			$this->bool = $value;
		} else {
			$this->string = (string) $value;
		}
	}
	
	public function getValue()
	{
		if ($this->number !== NULL) {
			return $this->number;
		} else if ($this->bool !== NULL) {
			return $this->bool;
		} else {
			return $this->string;
		}
	}
	
	private function reset()
	{
		$this->string = NULL;
		$this->number = NULL;
		$this->bool = NULL;
	}

	public function __toString()
	{
		return (string) $this->getValue();
	}

}
