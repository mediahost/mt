<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Stock $stock
 * @property int $quantity
 */
class BasketItem extends BaseEntity
{

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="Basket", inversedBy="items") */
	protected $basket;

	/** @ORM\ManyToOne(targetEntity="Stock") */
	protected $stock;
	
	/** @ORM\Column(type="integer") */
	protected $quantity;

}
