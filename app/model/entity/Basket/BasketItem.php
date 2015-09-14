<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
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
		$price = $this->stock->getPrice($level);
		$priceValue = $withVat ? $price->withVat : $price->withoutVat;
		$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;
		return $exchangedValue * $this->quantity;
	}

}
