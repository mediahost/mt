<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Stock;
use Nette\Reflection\ClassType;

trait StockPrices
{

	/** @var string */
	private $priceBase = self::DEFAULT_PRICE_BASE;

	/** @var integer */
	private $priceVersion = self::DEFAULT_PRICE_VERSION;

	public static function getPriceProperties()
	{
		$properties = [];
		$reflection = new ClassType(Stock::getClassName());
		foreach ($reflection->properties as $property) {
			if (preg_match('/^price(\d+)$/', $property->name, $matches)) {
				$properties[$matches[1]] = $matches[0];
			}
		}
		return $properties;
	}

	public function recalculatePrices()
	{
		$this->recalculateVersionPrices();
		$this->recalculateGroupPrices();
	}

	public function recalculateVersionPrices()
	{
		// TODO: implement
		return $this;
	}

	public function recalculateGroupPrices()
	{
		$priceProperties = self::getPriceProperties();
		$defaultPrice = $this->getPrice();
		foreach ($priceProperties as $level => $property) {
			$discount = $this->getDiscountByLevel($level);
			if ($discount) {
				$this->$property = $discount->getDiscountedPrice($defaultPrice);
			} else {
				$this->$property = $defaultPrice;
			}
		}
		return $this;
	}

}
