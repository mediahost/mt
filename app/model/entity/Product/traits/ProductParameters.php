<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\EntityException;
use App\Model\Entity\Parameter;
use App\Model\Entity\ParameterProperty;
use App\Model\Entity\Product;
use Nette\MemberAccessException;
use Nette\Reflection\ClassType;

trait ProductParameters
{
	// <editor-fold defaultstate="collapsed" desc="Strings">

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS1;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS2;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS3;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS4;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS5;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS6;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS7;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS8;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS9;

	/** @ORM\Column(type="string", nullable=true) */
	private $parameterS10;

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="Integers">

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN1;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN2;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN3;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN4;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN5;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN6;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN7;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN8;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN9;

	/** @ORM\Column(type="integer", nullable=true) */
	private $parameterN10;

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="Booleans">

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB1;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB2;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB3;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB4;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB5;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB6;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB7;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB8;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB9;

	/** @ORM\Column(type="boolean", nullable=true) */
	private $parameterB10;

	// </editor-fold>

	public function setParameter($code, $value = NULL)
	{
		$propertyName = 'parameter' . $code;
		if (property_exists($this, $propertyName)) {
			if (Parameter::checkCodeHasType($code, Parameter::STRING)) {
				$this->$propertyName = (string) $value;
			} else if (Parameter::checkCodeHasType($code, Parameter::INTEGER)) {
				$this->$propertyName = (int) $value;
			} else if (Parameter::checkCodeHasType($code, Parameter::BOOLEAN)) {
				$this->$propertyName = (bool) $value;
			} else {
				throw new EntityException('For this parameter we have no method to process');
			}
		}
	}

	public function getParameter($code)
	{
		$propertyName = 'parameter' . $code;
		if (property_exists($this, $propertyName)) {
			return $this->$propertyName;
		} else {
			$class = get_class($this);
			return new MemberAccessException("Cannot read an undeclared property $class::\$$propertyName.");
		}
	}

	public function clearParameters($type = NULL)
	{
		foreach (self::getParameterProperties($type = NULL) as $property) {
			$this->$property = NULL;
		}
		return $this;
	}

	public static function getParameterProperties($type = NULL)
	{
		if (!in_array($type, Parameter::getAllowedTypes())) {
			$type = '\w';
		}
		$properties = [];
		$reflection = new ClassType(Product::getClassName());
		foreach ($reflection->properties as $property) {
			if (preg_match('/^parameter' . $type . '\d+$/', $property->name)) {
				$parameterProperty = new ParameterProperty($property->name, $type);
				$properties[] = $parameterProperty;
			}
		}
		return $properties;
	}

}
