<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Stock $stock
 * @property Group $group
 * @property Discount $discount
 */
class GroupDiscount extends BaseEntity
{

	use Identifier;
	
    /** @ORM\ManyToOne(targetEntity="Stock", inversedBy="groupDiscounts") */
    protected $stock;

    /** @ORM\ManyToOne(targetEntity="Group") */
	protected $group;

    /** @ORM\OneToOne(targetEntity="Discount", cascade={"persist", "remove"}) */
	protected $discount;
	
	public function __construct(Group $group = NULL)
	{
		if ($group) {
			$this->group = $group;
		}
		parent::__construct();
	}
	
	public function __toString()
	{
		return (string) $this->name;
	}

}
