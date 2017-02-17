<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Order $order
 * @property Stock $stock
 * @property string $name
 * @property Price $price
 * @property Vat $vat
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
		$vat = $this->getVat();
		$price = new Price($vat, $this->price);
		return $price;
	}

	/** @return Vat */
	public function getVat()
	{
		return new Vat($this->vat ? $this->vat : 0);
	}

	public function getTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$price = $this->getPrice();
		$priceValue = $withVat ? $price->withVat : $price->withoutVat;
		$fromCurrency = $this->order->shopVariant->currency;
		$toCurrency = $this->order->currency;
		$exchangedValue = $exchange ? $exchange->change($priceValue, $fromCurrency, $toCurrency, Price::PRECISION) : $priceValue;
		return $exchangedValue * $this->quantity;
	}

}
