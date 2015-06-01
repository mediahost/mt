<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;
use App\Model\Entity\Price;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @property Price $price
 * @property float $priceVat
 * @property ArrayCollection $groupDiscounts
 */
trait ProductPrices
{

	/** @ORM\OneToOne(targetEntity="Price", cascade={"persist", "remove"}) */
	protected $price;
	
	/** @ORM\Column(type="float", nullable=true) */
	protected $purchasePrice;
	
	/** @ORM\Column(type="float", nullable=true) */
	protected $oldPrice;

	/** @ORM\OneToMany(targetEntity="GroupDiscount", mappedBy="product", cascade={"persist", "remove"}) */
	protected $groupDiscounts;

	public function getPrice(Group $group = NULL)
	{
		if ($group) {
			$discount = $this->getDiscountByGroup($group);
			if ($discount) {
				return $discount->getDiscountedPrice($this->price);
			}
		}
		return $this->price;
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
	}

	public function removeDiscountsByGroup(Group $group)
	{
		$removeWithGroup = function ($key, GroupDiscount $groupDiscount) use ($group) {
			if ($groupDiscount->group->id === $group->id) {
				$this->groupDiscounts->removeElement($groupDiscount);
			}
			return TRUE;
		};
		$this->groupDiscounts->forAll($removeWithGroup);
	}

	/** @return GroupDiscount|NULL */
	protected function getGroupDiscountByGroup(Group $group)
	{
		$groupDiscount = NULL;
		$hasGroup = function (GroupDiscount $groupDiscount) use ($group) {
			return $groupDiscount->group->id === $group->id;
		};
		$groupDiscounts = $this->groupDiscounts->filter($hasGroup);
		if ($groupDiscounts->count()) {
			$groupDiscount = $groupDiscounts->first();
		}
		return $groupDiscount;
	}

	/** @return Discount|NULL */
	protected function getDiscountByGroup(Group $group)
	{
		$discount = NULL;
		$groupDiscount = $this->getGroupDiscountByGroup($group);
		if ($groupDiscount) {
			$discount = $groupDiscount->discount;
		}
		return $discount;
	}

}
