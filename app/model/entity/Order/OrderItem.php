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
 * @property string $name
 * @property Price $price
 * @property int $quantity
 */
class OrderItem extends BaseEntity
{

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="Order", inversedBy="items") */
	protected $order;

	/** @ORM\ManyToOne(targetEntity="Stock") */
	protected $stock;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\Column(type="float", nullable=true) */
	private $price;

	/** @ORM\Column(type="float", nullable=true) */
	private $vat;

	/** @ORM\Column(type="integer") */
	protected $quantity;

	/** @return OrderItem */
	public function setPrice(Price $price)
	{
		$this->price = $price->withoutVat;
		$this->vat = $price->vat->value;
		return $this;
	}

	/** @return Price */
	public function getPrice()
	{
		$vat = new Vat(NULL, $this->vat);
		return new Price($vat, $this->price);
	}

	public function getTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$price = $this->getPrice();
		$priceValue = $withVat ? $price->withVat : $price->withoutVat;
		$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;
		return $exchangedValue * $this->quantity;
	}

}
