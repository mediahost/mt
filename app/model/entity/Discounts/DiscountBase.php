<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\MappedSuperclass
 *
 * @property string $type
 * @property float $value
 * @property Discount $discount
 */
abstract class DiscountBase extends BaseEntity
{

	const PERCENTAGE = 'percent';
	const FIXED_PRICE = 'fixed';
	const MINUS_VALUE = 'minus';
	const DEFAULT_TYPE = self::PERCENTAGE;

	use Identifier;

	/** @ORM\Column(type="string", length=20) */
	protected $type = self::DEFAULT_TYPE;

	/** @ORM\Column(type="float") */
	protected $value = 0;
	
	public function __construct($value = 0, $type = self::DEFAULT_TYPE)
	{
		parent::__construct();
		$this->setValue($value, $type);
	}
	
	public function setValue($value = 0, $type = NULL)
	{
		if ($type !== NULL) {
			$this->type = $type;
		}
		switch ($this->type) {
			case self::PERCENTAGE:
				$value = (0 <= $value && $value <= 100) ? $value : ($value > 100 ? 100 : 0);
				break;
			default:
				$value = 0 <= $value ? $value : 0;
				break;
		}
		$this->value = $value;
		return $this;
	}
	
	public function getDiscountedPrice(Price $price)
	{
		$discounted = new Price($price->vat);
		switch ($this->type) {
			case self::PERCENTAGE:
				$amount = $price->withoutVat * ($this->value / 100);
				$value = $price->withoutVat - $amount;
				break;
			case self::FIXED_PRICE:
				$value = $this->value;
				break;
			case self::MINUS_VALUE:
				$value = $price->withoutVat - $this->value;
				break;
			default:
				$value = $price->withoutVat;
				break;
		}
		$discounted->setWithoutVat($value);
		return $discounted;
	}

}
