<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Stock;
use Nette\Reflection\ClassType;

trait StockPrices
{

	public static function getPriceProperties($type = 'price')
	{
		switch ($type) {
			case 'price':
				$suffixRegExp = '\d+';
				break;
			case 'defaultPrice':
				$suffixRegExp = '\w\d+';
				break;
			default:
				return [];
		}
		$properties = [];
		$reflection = new ClassType(Stock::getClassName());
		foreach ($reflection->properties as $property) {
			if (preg_match('/^' . $type . '(' . $suffixRegExp . ')$/', $property->name, $matches)) {
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
		$attr = 'defaultPrice';
		$defaultAttr = $attr . self::DEFAULT_PRICE_BASE . self::DEFAULT_PRICE_VERSION;

		foreach (self::getPriceProperties($attr) as $key => $property) {
			list($shopLetter, $shopNumber) = self::parseShopId($key);
			$propertyAttr = $attr . $key;

			if ($property !== $defaultAttr && $this->isSynchronizePrice($shopLetter, $shopNumber)) {
				switch ($shopNumber) {
					case 1: // EUR
					default:
						$this->$propertyAttr = $this->$defaultAttr;
						break;
					case 2: // CZK
						$this->$propertyAttr = $this->$defaultAttr * self::RECALCULATE_RATE_CZK;
						break;
					case 3: // PLN
						$this->$propertyAttr = $this->$defaultAttr * self::RECALCULATE_RATE_PLN;
						break;
				}
			}
		}

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

	public static function parseShopId($shopId)
	{
		$shopLetter = substr($shopId, 0, 1);
		$shopNumber = substr($shopId, 1);
		return [$shopLetter, $shopNumber];
	}

}
