<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property int $value
 * @property-read float $percent
 * @property-read float $downDecimal
 * @property-read float $upDecimal
 */
class Vat extends BaseEntity
{

	const HIGH = 1;
	const LOW = 2;
	const NONE = 3;

	use Identifier;

	/** @ORM\Column(type="float") */
	protected $value;

	public function __construct($id, $value)
	{
		$this->setId($id);
		$this->setValue($value);
		parent::__construct();
	}

	public function setValue($value)
	{
		if (!is_numeric($value) || $value < 0 || 100 < $value) {
			throw new InvalidArgumentException($value . ' $value must be a number and greater or equal then 0 and lower than 100.');
		}
		$this->value = $value;

		return $this;
	}

	protected function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @example 19.5% = float 19.5
	 * @return float
	 */
	public function getPercent()
	{
		return $this->value;
	}

	/**
	 * @example 19.5% = float 0.195
	 * @return float
	 */
	public function getDownDecimal()
	{
		return $this->value / 100;
	}

	/**
	 * @example 19.5% = float 1.195
	 * @return float
	 */
	public function getUpDecimal()
	{
		return $this->downDecimal + 1;
	}

	public function isNone()
	{
		return $this->id === self::NONE;
	}

	public function __toString()
	{
		return (string)((int)$this->percent . '%');
	}

}
