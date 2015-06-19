<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Parameter;
use App\Model\Entity\Product;
use Nette\MemberAccessException;
use Nette\Reflection\ClassType;

/**
 * @property array $tags
 * @property array $signs
 * @property array $parameters
 */
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

	public function setParameter($type, $value = NULL)
	{
		$propertyName = 'parameter' . $type;
		if (property_exists($this, $propertyName)) {
			if (preg_match('/^' . Parameter::STRING . '/', $type)) {
				$this->$propertyName = (string) $value;
			} else if (preg_match('/^' . Parameter::INTEGER . '/', $type)) {
				$this->$propertyName = (int) $value;
			} else if (preg_match('/^' . Parameter::BOOLEAN . '/', $type)) {
				$this->$propertyName = (bool) $value;
			}
		}
	}

	public function getParameter($type)
	{
		$propertyName = 'parameter' . $type;
		if (property_exists($this, $propertyName)) {
			return $this->$propertyName;
		} else {
			$class = get_class($this);
			return new MemberAccessException("Cannot read an undeclared property $class::\$$propertyName.");
		}
	}

	public function clearParameters()
	{
		foreach (self::getParameterProperties() as $property) {
			$this->$property = NULL;
		}
		return $this;
	}

	public function &__get($name)
	{
		$types = Parameter::STRING;
		$types .= '|' . Parameter::INTEGER;
		$types .= '|' . Parameter::BOOLEAN;
		if (preg_match('/^parameter([' . $types . ']\d+)$/', $name, $matches)) {
			$value = $this->getParameter($matches[1]);
			return $value;
		} else {
			return parent::__get($name);
		}
	}

	public static function getParameterProperties()
	{
		$properties = [];
		$reflection = new ClassType(Product::getClassName());
		foreach ($reflection->properties as $property) {
			if (preg_match('/^parameter\w\d+$/', $property->name)) {
				$properties[] = $property;
			}
		}
		return $properties;
	}

}
