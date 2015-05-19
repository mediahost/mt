<?php

namespace Test\Examples\Model\Entity\Asociation\OneToManyBidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 * 
 * @property string $name
 * @property Product $product
 */
class Feature extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $name;
	
    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="features")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

}
