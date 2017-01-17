<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;

trait StockGroupPrices
{

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

	/** @ORM\OneToMany(targetEntity="GroupDiscount", mappedBy="stock", cascade={"persist", "remove"}) */
	protected $groupDiscounts;

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
		$this->recalculatePrices();

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

	/** @return Discount|NULL */
	public function getDiscountByGroup(Group $group)
	{
		return $this->getDiscountByLevel($group->level);
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

	/** @return GroupDiscount|NULL */
	public function getGroupDiscountByGroup(Group $group)
	{
		return $this->getGroupDiscountByLevel($group->level);
	}

	/** @return GroupDiscount|NULL */
	protected function getGroupDiscountByLevel($level)
	{
		$groupDiscount = NULL;
		$hasGroup = function (GroupDiscount $groupDiscount) use ($level) {
			return (int)$groupDiscount->group->level === (int)$level;
		};
		$groupDiscounts = $this->groupDiscounts->filter($hasGroup);
		if ($groupDiscounts->count()) {
			$groupDiscount = $groupDiscounts->first();
		}
		return $groupDiscount;
	}

	/** @return bool */
	public function hasDiscounts()
	{
		return (bool)$this->groupDiscounts->count();
	}

	protected function getLevelFromGroup(Group $group = NULL)
	{
		return $group ? $group->level : NULL;
	}

}
