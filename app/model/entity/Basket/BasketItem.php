<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Basket $basket
 * @property Stock $stock
 * @property int $quantity
 */
class BasketItem extends BaseEntity
{

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="Basket", inversedBy="items") */
	protected $basket;

	/** @ORM\ManyToOne(targetEntity="Stock") */
	protected $stock;

	/** @ORM\Column(type="integer") */
	protected $quantity;

	public function getTotalPrice(Exchange $exchange = NULL, $level = NULL, $withVat = TRUE)
	{
		$price = $this->getStock()->getPrice($level);
		$priceValue = $withVat ? $price->withVat : $price->withoutVat;
		if ($exchange && $price->convertible) {
			$fromCurrency = $this->basket->shopVariant->currency;
			$exchangedValue = $exchange->change($priceValue, $fromCurrency, NULL, Price::PRECISION);
		} else {
			$exchangedValue = $priceValue;
		}
		return $exchangedValue * $this->quantity;
	}

	public function getStock()
	{
		$this->stock->setShopVariant($this->basket->shopVariant);
		return $this->stock;
	}

}
