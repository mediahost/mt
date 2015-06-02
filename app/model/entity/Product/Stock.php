<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\StockRepository")
 *
 * @property string $name
 */
class Stock extends BaseEntity
{

	use Identifier;
	
    /** @ORM\ManyToOne(targetEntity="Product", inversedBy="stocks") */
    protected $product;
	
    /** @ORM\ManyToOne(targetEntity="Variant") */
    protected $variant1;
	
    /** @ORM\ManyToOne(targetEntity="Variant") */
    protected $variant2;
	
    /** @ORM\ManyToOne(targetEntity="Variant") */
    protected $variant3;
	
	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;
	
	/** @ORM\Column(type="integer") */
	protected $quantity;

	public function __construct()
	{
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
