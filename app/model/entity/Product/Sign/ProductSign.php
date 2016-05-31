<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 */
class ProductSign extends BaseEntity
{

	use Model\Timestampable\Timestampable;

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Product", inversedBy="signs")
	 */
	protected $product;

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Sign", inversedBy="products")
	 */
	protected $sign;

}
