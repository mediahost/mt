<?php

namespace App\Model\Entity;

use Nette\Object;

/**
 * @property float $withVat
 * @property float $withoutVat
 * @property Vat $vat
 * @property float $vatSum
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

	public function __construct(Vat $vat = NULL, $withoutVat = NULL)
	{
		if ($vat) {
			$this->setVat($vat);
		}
		if ($withoutVat > 0) {
			$this->setWithoutVat($withoutVat);
		}
	}

	public function setVat(Vat $vat)
	{
		$this->vat = $vat;
		return $this;
	}

	public function getVat()
	{
		return $this->vat;
	}

	public function getPrecision()
	{
		return $this->precision;
	}

	public function setWithVat($value)
	{
		$this->value = round($value / $this->vat->upDecimal, $this->precision);
		return $this;
	}

	/** @return float */
	public function getWithVat()
	{
		return (float) round($this->value * $this->vat->upDecimal, $this->precision);
	}

	public function setWithoutVat($value)
	{
		$this->value = (float) round($value, $this->precision);
		return $this;
	}

	/** @return float */
	public function getWithoutVat()
	{
		return (float) round($this->value, $this->precision);
	}

	/** @return float */
	public function getVatSum()
	{
		return (float) round($this->value * $this->vat->downDecimal, $this->precision);
	}

	public function __toString()
	{
		$string = (string) $this->getWithoutVat();
		if ($this->vat && $this->vat->percent > 0) {
			$string .= ' (+' . $this->vat . ')';
		}
		return $string;
	}

}
