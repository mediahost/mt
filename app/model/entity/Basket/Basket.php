<?php

namespace App\Model\Entity;

use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\Exception\MissingItemException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\BasketRepository")
 *
 * @property ArrayCollection $items
 */
class Basket extends BaseEntity
{

	use Identifier;

use Model\Timestampable\Timestampable;

	public function __construct(User $user = NULL)
	{
		if ($user) {
			$this->setUser($user);
		}
		$this->items = new ArrayCollection();
		parent::__construct();
	}

	/** @ORM\OneToOne(targetEntity="User", inversedBy="basket") */
	protected $user;

	/** @ORM\OneToMany(targetEntity="BasketItem", mappedBy="basket", cascade={"persist", "remove"}, orphanRemoval=true) */
	protected $items;

	public function setUser(User $user)
	{
		$this->user = $user;
		$user->basket = $this;
		return $this;
	}

	public function setItem(Stock $stock, $quantity)
	{
		if ($quantity > $stock->inStore) {
			throw new InsufficientQuantityException();
		}

		$isInItems = function ($key, BasketItem $item) use ($stock) {
			return $stock->id === $item->stock->id;
		};
		$changeQuantity = function ($key, BasketItem $item) use ($stock, $quantity) {
			if ($stock->id === $item->stock->id) {
				if ($quantity > 0) {
					$item->quantity = $quantity;
				} else {
					$this->items->removeElement($item);
				}
			}
		};

		if ($this->items->exists($isInItems)) {
			$this->items->forAll($changeQuantity);
		} else {
			$item = new BasketItem();
			$item->basket = $this;
			$item->stock = $stock;
			$item->quantity = $quantity;
			$this->items->add($item);
		}
		return $this;
	}
	
	public function getItemCount(Stock $stock)
	{
		foreach ($this->items as $item) {
			if ($item->stock->id === $stock->id) {
				return $item->quantity;
			}
		}
		throw new MissingItemException();
	}

}
