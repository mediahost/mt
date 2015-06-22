<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;
use App\Model\Entity\Price;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use Nette\Reflection\ClassType;

/**
 * @property Price $price
 * @property float $priceVat
 * @property float $purchasePrice
 * @property float $oldPrice
 */
trait StockPrices
{

	/** @ORM\Column(type="float", nullable=true) */
	protected $purchasePrice;

	/** @ORM\Column(type="float", nullable=true) */
	protected $oldPrice;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vat;

	/** @ORM\Column(type="float", nullable=true) */
	private $defaultPrice;

	// <editor-fold defaultstate="collapsed" desc="Group Prices">

	/** @ORM\Column(type="float", nullable=true) */
	private $price1;

	/** @ORM\Column(type="float", nullable=true) */
	private $price2;

	/** @ORM\Column(type="float", nullable=true) */
	private $price3;

	/** @ORM\Column(type="float", nullable=true) */
	private $price4;

	/** @ORM\Column(type="float", nullable=true) */
	private $price5;

	/** @ORM\Column(type="float", nullable=true) */
	private $price6;

	/** @ORM\Column(type="float", nullable=true) */
	private $price7;

	/** @ORM\Column(type="float", nullable=true) */
	private $price8;

	/** @ORM\Column(type="float", nullable=true) */
	private $price9;

	/** @ORM\Column(type="float", nullable=true) */
	private $price10;

	// </editor-fold>

	/** @ORM\OneToMany(targetEntity="GroupDiscount", mappedBy="product", cascade={"persist", "remove"}) */
	private $groupDiscounts;

	public function getPrice(Group $group = NULL)
	{
		$level = $this->getLevelFromGroup($group);
		$priceProperties = self::getPriceProperties();
		if ($level && array_key_exists($level, $priceProperties)) {
			$priceProperty = $priceProperties[$level];
			$priceValue = $this->$priceProperty;
		}
		$priceValue = $this->defaultPrice;
		return new Price($this->vat, $priceValue);
	}

	public function setPrice($value, Group $group = NULL, $withVat = FALSE)
	{
		$price = new Price($this->vat);

		if ($withVat) {
			$price->withVat = $value;
		} else {
			$price->withoutVat = $value;
		}

		$priceProperties = self::getPriceProperties();
		$level = $this->getLevelFromGroup($group);
		if ($level && array_key_exists($level, $priceProperties)) {
			$priceProperty = $priceProperties[$level];
			$this->$priceProperty = $price->withoutVat;
		} else {
			$this->defaultPrice = $price->withoutVat;
		}
		return $this;
	}

	public function setDefaltPrice($value, $withVat = FALSE)
	{
		$this->setPrice($value, NULL, $withVat);
		$this->recalculateOtherPrices();
		return $this;
	}

	protected function getLevelFromGroup(Group $group = NULL)
	{
		return $group ? $group->level : NULL;
	}
	
	public static function getPriceProperties()
	{
		$properties = [];
		$reflection = new ClassType(Stock::getClassName());
		foreach ($reflection->properties as $property) {
			if (preg_match('/^parameter(\d+)$/', $property->name, $matches)) {
				$properties[$matches[1]] = $matches[0];
			}
		}
		return $properties;
	}

	protected function recalculateOtherPrices()
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

	public function addDiscount(Discount $discount, Group $group)
	{
		$groupDiscount = $this->getGroupDiscountByGroup($group);
		if (!$groupDiscount) {
			$groupDiscount = new GroupDiscount();
			$groupDiscount->product = $this;
			$groupDiscount->group = $group;
			$groupDiscount->discount = $discount;
			$this->groupDiscounts->add($groupDiscount);
		} else {
			$groupDiscount->discount->type = $discount->type;
			$groupDiscount->discount->value = $discount->value;
		}
		$this->recalculateOtherPrices();
		
		return $this;
	}

//	public function removeDiscountsByGroup(Group $group)
//	{
//		$removeWithGroup = function ($key, GroupDiscount $groupDiscount) use ($group) {
//			if ($groupDiscount->group->id === $group->id) {
//				$this->groupDiscounts->removeElement($groupDiscount);
//			}
//			return TRUE;
//		};
//		$this->groupDiscounts->forAll($removeWithGroup);
//		$this->recalculateOtherPrices();
//		
//		return $this;
//	}

	/** @return GroupDiscount|NULL */
	protected function getGroupDiscountByLevel($level)
	{
		$groupDiscount = NULL;
		$hasGroup = function (GroupDiscount $groupDiscount) use ($level) {
			return $groupDiscount->group->level === $level;
		};
		$groupDiscounts = $this->groupDiscounts->filter($hasGroup);
		if ($groupDiscounts->count()) {
			$groupDiscount = $groupDiscounts->first();
		}
		return $groupDiscount;
	}

	/** @return Discount|NULL */
	protected function getDiscountByLevel($level)
	{
		$discount = NULL;
		$groupDiscount = $this->getGroupDiscountByLevel($level);
		if ($groupDiscount) {
			$discount = $groupDiscount->discount;
		}
		return $discount;
	}

}
