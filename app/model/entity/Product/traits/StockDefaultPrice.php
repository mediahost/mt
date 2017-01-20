<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Group;
use App\Model\Entity\Price;

trait StockDefaultPrice
{

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPrice; // TODO: delete

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPriceA1;

	/** @ORM\Column(type="boolean") */
	private $synchronizePriceA1 = TRUE;

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPriceA2;

	/** @ORM\Column(type="boolean") */
	private $synchronizePriceA2 = TRUE;

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPriceB1;

	/** @ORM\Column(type="boolean") */
	private $synchronizePriceB1 = TRUE;

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPriceB2;

	/** @ORM\Column(type="boolean") */
	private $synchronizePriceB2 = TRUE;

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPriceB3;

	/** @ORM\Column(type="boolean") */
	private $synchronizePriceB3 = TRUE;

	/**
	 * @param Group|int $groupOrLevel Group or level
	 * @return Price
	 */
	public function getPrice($groupOrLevel = NULL)
	{
		if ($groupOrLevel instanceof Group) {
			$level = $this->getLevelFromGroup($groupOrLevel);
		} else {
			$level = $groupOrLevel;
		}
		$priceProperties = self::getPriceProperties();
		if ($level && array_key_exists($level, $priceProperties)) {
			$priceProperty = $priceProperties[$level];
		} else {
			$priceProperty = 'defaultPrice' . $this->priceBase . $this->priceVersion;
		}
		return new Price($this->getVat(), $this->$priceProperty);
	}

	public function setDefaultPrice($value, $withVat = FALSE)
	{
		$this->setPrice($value, NULL, $withVat);
		$this->recalculatePrices();
		return $this;
	}

	public function setPrice($value, Group $group = NULL, $withVat = FALSE)
	{
		$price = new Price($this->vat, $value, !$withVat);

		$priceProperties = self::getPriceProperties();
		$level = $this->getLevelFromGroup($group);
		if ($level && array_key_exists($level, $priceProperties)) {
			$priceProperty = $priceProperties[$level];
		} else {
			$priceProperty = 'defaultPrice' . $this->priceBase . $this->priceVersion;
		}
		$this->$priceProperty = $price->withoutVat;

		$this->setChangePohodaData();

		return $this;
	}

	public function isSynchronizePrice($shopLetter = self::DEFAULT_PRICE_BASE, $shopNumber = self::DEFAULT_PRICE_VERSION)
	{
		$attr = 'synchronizePrice' . $shopLetter . $shopNumber;
		return $this->$attr;
	}

	public function setSynchronizePrice($shopLetter = self::DEFAULT_PRICE_BASE, $shopNumber = self::DEFAULT_PRICE_VERSION, $value = TRUE)
	{
		$attr = 'synchronizePrice' . $shopLetter . $shopNumber;
		$this->$attr = $value;
		return $this;
	}

}
