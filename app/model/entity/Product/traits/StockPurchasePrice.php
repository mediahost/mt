<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Price;

trait StockPurchasePrice
{

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePrice;

	/** @return Price|NULL */
	public function getPurchasePrice()
	{
		if ($this->purchasePrice === NULL) {
			return NULL;
		}
		return new Price($this->getVat(), $this->purchasePrice);
	}

	public function setPurchasePrice($value, $withVat = FALSE)
	{
		if ($value === NULL) {
			$this->purchasePrice = NULL;
		} else {
			$price = new Price($this->getVat(), $value, !$withVat);
			$this->purchasePrice = $price->withoutVat;
		}
		
		$this->setChangePohodaData();
		
		return $this;
	}

}
