<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\BasketRepository")
 *
 * @property BasketItem[] $items
 */
class Basket extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;
	
	/** @ORM\OneToMany(targetEntity="BasketItem", mappedBy="basket") */
	protected $items;

}
