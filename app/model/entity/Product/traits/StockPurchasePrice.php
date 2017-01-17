<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Price;

trait StockPurchasePrice
{

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePrice; // TODO: delete

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePriceA1;

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePriceA2;

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePriceB1;

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePriceB2;

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePriceB3;

	/** @return Price|NULL */
	public function getPurchasePrice()
	{
		$priceAttr = 'purchasePrice' . $this->priceBase . $this->priceVersion;
		if ($this->$priceAttr === NULL) {
			return NULL;
		}
		return new Price($this->getVat(), $this->$priceAttr);
	}

	public function setPurchasePrice($value, $withVat = FALSE)
	{
		$priceAttr = 'purchasePrice' . $this->priceBase . $this->priceVersion;
		if ($value === NULL) {
			$this->$priceAttr = NULL;
		} else {
			$price = new Price($this->getVat(), $value, !$withVat);
			$this->$priceAttr = $price->withoutVat;
		}
		
		$this->setChangePohodaData();
		
		return $this;
	}

}
