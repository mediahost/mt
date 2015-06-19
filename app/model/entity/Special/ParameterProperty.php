<?php

namespace App\Model\Entity;

use Nette\Object;

class ParameterProperty extends Object
{

	/** string */
	private $name;

	/** string */
	private $type;

	/** string */
	private $order;

	public function __construct($name)
	{
		$this->name = $name;
		if (preg_match('/^parameter(\w)(\d+)/', $name, $matches)) {
			$this->type = $matches[1];
			$this->order = $matches[2];
		}
	}

	public function __toString()
	{
		return (string) $this->name;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getOrder()
	{
		return $this->order;
	}
	
	public function getCode()
	{
		return $this->type . $this->order;
	}

}
