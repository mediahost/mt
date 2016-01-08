<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\DateTime;
use Nette\Utils\Random;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 *
 * @property string $type
 * @property float $value
 * @property DateTime $activeFrom
 * @property DateTime $activeTo
 * @property string $code
 */
class Voucher extends DiscountBase
{

	const CODE_LENGHT = 7;

	/** @ORM\Column(type="datetime", nullable=true) */
    protected $activeFrom;

	/** @ORM\Column(type="datetime", nullable=true) */
    protected $activeTo;

	/** @ORM\Column(type="string", length=32) */
	protected $code;

	public function __construct($value = 0, $type = self::DEFAULT_TYPE)
	{
		$this->code = Random::generate(self::CODE_LENGHT);
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
	
	public function getValueString($currency = NUll)
	{
		switch ($this->type) {
			case self::PERCENTAGE:
				$suffix = '%';
				break;
			default:
				$suffix = $currency ? ' ' . $currency : NULL;
				break;
		}
		return '-' . $this->value . $suffix;
	}
	
	public function __toString()
	{
		return $this->code . ' ' . $this->getValueString();
	}
	
	static public function getTypesArray()
	{
		return [
			self::PERCENTAGE => self::PERCENTAGE,
			self::MINUS_VALUE => self::MINUS_VALUE,
		];
	}

}
