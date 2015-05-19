<?php

namespace Test\Examples\Model\Entity\Asociation\OneToOneUnidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 *
 * @property string $name
 * @property Shipping $shipping
 */
class Product extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=false) */
	protected $name;
	
    /**
     * @ORM\OneToOne(targetEntity="Shipping")
     * @ORM\JoinColumn(name="shipping_id", referencedColumnName="id")
     **/
    protected $shipping;

}
