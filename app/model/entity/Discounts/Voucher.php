<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Nette\Utils\DateTime;
use Nette\Utils\Random;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 *
 * @property string $type
 * @property float $value
 * @property bool $active
 * @property DateTime $activeFrom
 * @property DateTime $activeTo
 * @property string $code
 * @property string $currency
 */
class Voucher extends DiscountBase
{

	const CODE_LENGTH = 7;

	/** @ORM\Column(type="datetime", nullable=true) */
    protected $activeFrom;

	/** @ORM\Column(type="datetime", nullable=true) */
    protected $activeTo;

	/** @ORM\Column(type="string", length=32) */
	protected $code;

	/** @ORM\Column(type="string", length=3, nullable=true) */
	protected $currency;

	/** @ORM\ManyToMany(targetEntity="Basket", mappedBy="vouchers") */
	protected $baskets;

	/** @ORM\ManyToMany(targetEntity="Order", mappedBy="vouchers") */
	protected $orders;

	public function __construct($value = 0, $type = self::DEFAULT_TYPE)
	{
		$this->code = Random::generate(self::CODE_LENGTH);
		parent::__construct($value, $type);
	}

	public function getActive($time = 'now')
	{
		$now = $time instanceof DateTime ? $time : new DateTime($time);
		$activeFrom = !$this->activeFrom || $this->activeFrom <= $now;
		$activeTo = !$this->activeTo || $now <= $this->activeTo;
		return $activeFrom && $activeTo;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function getCode()
	{
		return Strings::upper($this->code);
	}
	
	public function getSymbol($currency = NUll)
	{
		switch ($this->type) {
			case self::PERCENTAGE:
				return '%';
			default:
				return $currency ? ' ' . $currency : NULL;
		}
	}
	
	public function getValueString($currency = NUll, Exchange $exchange = NULL)
	{
		switch ($this->type) {
			case self::PERCENTAGE:
				$string = Price::floatToStr($this->value) . $this->getSymbol($currency);
				break;
			default:
				if ($exchange) {
					$string = $exchange->format($this->value);
				} else {
					$string = Price::floatToStr($this->value) . $this->getSymbol($currency);
				}
				break;
		}
		return $string;
	}
	
	public function getDiscountValue($fromValue, Exchange $exchange = NULL)
	{
		switch ($this->type) {
			case self::PERCENTAGE:
				$value = ($fromValue / 100) * $this->value;
				break;
			default:
				if ($exchange) {
					$value = $exchange->change($this->value, NULL, NULL, 2);
				} else {
					$value = $this->value;
				}
				break;
		}
		return $value;
	}
	
	public function __toString()
	{
		return $this->code . ' -' . $this->getValue() . $this->getSymbol();
	}
	
	static public function getTypesArray()
	{
		return [
			self::PERCENTAGE => self::PERCENTAGE,
			self::MINUS_VALUE => self::MINUS_VALUE,
		];
	}

}
