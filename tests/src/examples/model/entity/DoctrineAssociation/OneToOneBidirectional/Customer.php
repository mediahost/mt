<?php

namespace Test\Examples\Model\Entity\Asociation\OneToOneBidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Inverzed
 * @ORM\Entity
 *
 * @property string $name
 * @property Cart $cart
 */
class Customer extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;
	
    /**
     * @ORM\OneToOne(targetEntity="Cart", mappedBy="customer")
     **/
    protected $cart;

}
