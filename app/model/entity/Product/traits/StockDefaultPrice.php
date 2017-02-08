<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Group;
use App\Model\Entity\Price;

trait StockDefaultPrice
{

	/** @ORM\Column(type="float", nullable=true) */
	private $oldPrice; // TODO: delete

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
	public function getPrice($groupOrLevel = NULL, $priceBase = NULL, $priceVersion = NULL)
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
			$priceBase = $priceBase ? $priceBase : $this->priceBase;
			$priceVersion = $priceVersion ? $priceVersion : $this->priceVersion;
			$priceProperty = self::DEFAULT_PRICE_NAME . $priceBase . $priceVersion;
		}
		$price = new Price($this->getVat($priceBase), $this->$priceProperty);
		$price->convertible = FALSE;
		return $price;
	}

	public function getDefaultPrice($priceBase = NULL, $priceVersion = NULL)
	{
		return $this->getPrice(NULL, $priceBase, $priceVersion);
	}

	public function setDefaultPrice($value, $withVat = FALSE, $priceBase = NULL, $priceVersion = NULL, $recalculate = TRUE)
	{
		$this->setPrice($value, NULL, $withVat, $priceBase, $priceVersion);
		if ($recalculate && !$priceBase && !$priceVersion) {
			$this->recalculatePrices();
		}
		return $this;
	}

	public function setPrice($value, Group $group = NULL, $withVat = FALSE, $priceBase = NULL, $priceVersion = NULL)
	{
		$price = new Price($this->getVat($priceBase), $value, !$withVat);

		$priceProperties = self::getPriceProperties();
		$level = $this->getLevelFromGroup($group);
		if ($level && array_key_exists($level, $priceProperties)) {
			$priceProperty = $priceProperties[$level];
		} else {
			$priceBase = $priceBase ? $priceBase : $this->priceBase;
			$priceVersion = $priceVersion ? $priceVersion : $this->priceVersion;
			$priceProperty = self::DEFAULT_PRICE_NAME . $priceBase . $priceVersion;
		}
		$this->$priceProperty = $price->withoutVat;

		$this->setChangePohodaData();

		return $this;
	}

	public function isSynchronizePrice($shopLetter = self::DEFAULT_PRICE_BASE, $shopNumber = self::DEFAULT_PRICE_VERSION)
	{
		$attr = self::SYNCHRONIZE_PRICE_NAME . $shopLetter . $shopNumber;
		return $this->$attr;
	}

	public function setSynchronizePrice($shopLetter = self::DEFAULT_PRICE_BASE, $shopNumber = self::DEFAULT_PRICE_VERSION, $value = TRUE)
	{
		$attr = self::SYNCHRONIZE_PRICE_NAME . $shopLetter . $shopNumber;
		$this->$attr = $value;
		return $this;
	}

}
