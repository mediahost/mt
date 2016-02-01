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
 * @property Price $purchasePrice
 * @property Price $oldPrice
 * @property array $groupDiscounts
 * @property Vat $vat
 */
trait StockPrices
{

	/** @ORM\Column(type="float", nullable=true) */
	private $purchasePrice;

	/** @ORM\Column(type="float", nullable=true) */
	private $oldPrice;

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

	/** @ORM\Column(type="float", nullable=true) */
	private $price11;

	/** @ORM\Column(type="float", nullable=true) */
	private $price12;

	/** @ORM\Column(type="float", nullable=true) */
	private $price13;

	/** @ORM\Column(type="float", nullable=true) */
	private $price14;

	// </editor-fold>

	/** @ORM\OneToMany(targetEntity="GroupDiscount", mappedBy="stock", cascade={"persist", "remove"}) */
	protected $groupDiscounts;

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
			$priceValue = $this->$priceProperty;
		} else {
			$priceValue = $this->defaultPrice;
		}
		return new Price($this->vat, $priceValue);
	}

	/** @return Price|NULL */
	public function getPurchasePrice()
	{
		if ($this->purchasePrice === NULL) {
			return NULL;
		}
		return new Price($this->vat, $this->purchasePrice);
	}

	/** @return Price|NULL */
	public function getOldPrice()
	{
		if ($this->oldPrice === NULL) {
			return NULL;
		}
		return new Price($this->vat, $this->oldPrice);
	}

	/** @return Discount|NULL */
	public function getDiscountByGroup(Group $group)
	{
		return $this->getDiscountByLevel($group->level);
	}

	/** @return GroupDiscount|NULL */
	public function getGroupDiscountByGroup(Group $group)
	{
		return $this->getGroupDiscountByLevel($group->level);
	}

	/** @return bool */
	public function hasDiscounts()
	{
		return (bool) $this->groupDiscounts->count();
	}

	/*	 * ******************************************************************* */

	public function setDefaltPrice($value, $withVat = FALSE)
	{
		$this->setPrice($value, NULL, $withVat);
		$this->recalculateOtherPrices();
		return $this;
	}

	public function setPrice($value, Group $group = NULL, $withVat = FALSE)
	{
		$price = new Price($this->vat, $value, !$withVat);

		$priceProperties = self::getPriceProperties();
		$level = $this->getLevelFromGroup($group);
		if ($level && array_key_exists($level, $priceProperties)) {
			$priceProperty = $priceProperties[$level];
			$this->$priceProperty = $price->withoutVat;
		} else {
			$this->defaultPrice = $price->withoutVat;
		}
		
		$this->setChangePohodaData();
		
		return $this;
	}

	public function setPurchasePrice($value, $withVat = FALSE)
	{
		if ($value === NULL) {
			$this->purchasePrice = NULL;
		} else {
			$price = new Price($this->vat, $value, !$withVat);
			$this->purchasePrice = $price->withoutVat;
		}
		
		$this->setChangePohodaData();
		
		return $this;
	}

	public function setOldPrice($value, $withVat = FALSE)
	{
		if ($value === NULL) {
			$this->oldPrice = NULL;
		} else {
			$price = new Price($this->vat, $value, !$withVat);
			$this->oldPrice = $price->withoutVat;
		}
		return $this;
	}

	public function addDiscount(Discount $discount, Group $group)
	{
		$groupDiscount = $this->getGroupDiscountByLevel($group->level);
		if (!$groupDiscount) {
			$groupDiscount = new GroupDiscount();
			$groupDiscount->stock = $this;
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

	public function removeDiscountsByGroup(Group $group)
	{
		$removedElements = [];
		$removeWithGroup = function ($key, GroupDiscount $groupDiscount) use ($group, &$removedElements) {
			if ($groupDiscount->group->id === $group->id) {
				$removedElements[] = $groupDiscount;
				$this->groupDiscounts->removeElement($groupDiscount);
			}
			return TRUE;
		};
		$this->groupDiscounts->forAll($removeWithGroup);
		
		return $removedElements;
	}

	/*	 * ******************************************************************* */

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

	/*	 * ******************************************************************* */

	protected function getLevelFromGroup(Group $group = NULL)
	{
		return $group ? $group->level : NULL;
	}

	public function recalculateOtherPrices()
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

	/** @return GroupDiscount|NULL */
	protected function getGroupDiscountByLevel($level)
	{
		$groupDiscount = NULL;
		$hasGroup = function (GroupDiscount $groupDiscount) use ($level) {
			return (int) $groupDiscount->group->level === (int) $level;
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
