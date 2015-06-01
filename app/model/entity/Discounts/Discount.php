<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $type
 * @property float $value
 * @property Discount $discount
 */
class Discount extends BaseEntity
{

	const PERCENTAGE = 'percent';
	const FIXED_PRICE = 'fixed';
	const MINUS_VALUE = 'minus';

	use Identifier;

	/** @ORM\Column(type="string", length=20) */
	protected $type = self::PERCENTAGE;

	/** @ORM\Column(type="float") */
	protected $value = 0;
	
	public function __construct($value = 0, $type = self::PERCENTAGE)
	{
		parent::__construct();
		$this->value = $value;
		$this->type = $type;
	}
	
	public function getDiscountedPrice(Price $price)
	{
		$discounted = new Price();
		$discounted->setVat($price->vat);
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
