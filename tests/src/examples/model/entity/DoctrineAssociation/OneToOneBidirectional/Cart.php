<?php

namespace Test\Examples\Model\Entity\Asociation\OneToOneBidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 * 
 * @property float $price
 * @property Customer $customer
 */
class Cart extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="float") */
	protected $price;
	
    /**
     * @ORM\OneToOne(targetEntity="Customer", inversedBy="cart")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     **/
    protected $customer;

}
