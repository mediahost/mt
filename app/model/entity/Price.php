<?php

namespace App\Model\Entity;

use Nette\Object;

/**
 * @property-write float $value
 * @property float $withVat
 * @property float $withoutVat
 * @property Vat $vat
 * @property float $vatSum
 * @property bool $convertible
 */
class Price extends Object
{

	const PRECISION = 2;

	/** @var float */
	private $value;

	/** @var Vat */
	private $vat;

	/** @var int */
	private $precision = self::PRECISION;

	/** @var bool */
	private $convertible = TRUE;

	public function __construct(Vat $vat, $value = NULL, $withoutVat = TRUE)
	{
		$this->setVat($vat);
		if ($value !== NULL) {
			if (!is_float($value)) {
				$value = self::strToFloat((string)$value);
			}
			$this->setValue($value, $withoutVat);
		}
	}

	/*	 * ******************************************************************* */

	public function setConvertible($value = TRUE)
	{
		$this->convertible = $value;
		return $this;
	}

	public function getConvertible()
	{
		return $this->convertible;
	}

	public function setVat(Vat $vat)
	{
		$this->vat = $vat;
		return $this;
	}

	public function setWithVat($value)
	{
		$this->value = round($value / $this->vat->upDecimal, $this->precision);
		return $this;
	}

	public function setWithoutVat($value)
	{
		$this->value = (float)round($value, $this->precision);
		return $this;
	}

	public function setValue($value, $withoutVat = TRUE)
	{
		if ($withoutVat) {
			$this->setWithoutVat($value);
		} else {
			$this->setWithVat($value);
		}
		return $this;
	}

	/*	 * ******************************************************************* */

	/** @return Vat */
	public function getVat()
	{
		return $this->vat;
	}

	/** @return float */
	public function getVatSum()
	{
		return (float)round($this->value * $this->vat->downDecimal, $this->precision);
	}

	/** @return float */
	public function getWithVat()
	{
		return (float)round($this->value * $this->vat->upDecimal, $this->precision);
	}

	/** @return float */
	public function getWithoutVat()
	{
		return (float)round($this->value, $this->precision);
	}

	public function getPrecision()
	{
		return $this->precision;
	}

	/*	 * ******************************************************************* */

	public function __toString()
	{
		return (string)$this->getWithoutVat();
	}

	public static function strToFloat($string)
	{
		$string = preg_replace('/\s+/', '', $string);
		return (float)preg_replace('/,/', '.', $string);
	}

	public static function floatToStr($float)
	{
		return preg_replace('/\./', ',', (string)$float);
	}

}
