<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="`group`")
 *
 * @property string $name
 * @property string $level
 * @property int $type
 * @property float $percentage
 * @property ArrayCollection $users
 */
class Group extends BaseEntity
{
	
	const TYPE_DEALER = 1;
	const TYPE_BONUS = 2;

	use Identifier;

	/** @ORM\Column(type="smallint") */
	protected $type = self::TYPE_DEALER;

	/** @ORM\Column(type="smallint") */
	protected $level;

	/** @ORM\Column(type="string", length=50) */
	protected $name;

	/** @ORM\Column(type="float", nullable=true) */
	protected $percentage;

	/** @ORM\ManyToMany(targetEntity="User", mappedBy="groups") */
	protected $users;

	public function __construct($level, $name = NULL)
	{
		$this->level = $level;
		if ($name) {
			$this->name = $name;
		}
		$this->users = new ArrayCollection();
		parent::__construct();
	}

	public function addUser(User $user)
	{
		if (!$this->users->contains($user)) {
			$this->users->add($user);
		}
		return $this;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function isDealerType()
	{
		return $this->type === self::TYPE_DEALER;
	}

	public function isBonusType()
	{
		return $this->type === self::TYPE_BONUS;
	}
	
	public function getDiscount()
	{
		return new Discount($this->percentage, Discount::PERCENTAGE);
	}
	
	public function getDiscountedPrice(Price $price)
	{
		$discount = $this->getDiscount();
		return $discount->getDiscountedPrice($price);
	}

}
