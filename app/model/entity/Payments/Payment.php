<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PaymentRepository")
 *
 * @property bool $active
 * @property string $name
 * @property Price $price
 * @property ArrayCollection $shippings
 */
class Payment extends BaseEntity
{
	
	const PERSONAL = 1;
	const ON_DELIVERY = 2;
	const BANK_ACCOUNT = 3;

	use Identifier;

	/** @ORM\Column(type="boolean") */
	protected $active;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\ManyToMany(targetEntity="Shipping", inversedBy="payments") */
	protected $shippings;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vat;

	/** @ORM\Column(type="float", nullable=true) */
	private $price;

	public function __construct()
	{
		$this->shippings = new ArrayCollection();
		parent::__construct();
	}

	public function getPrice(Basket $basket = NULL)
	{
		$price = $basket ? $this->getPriceByBasket($basket) : $this->price;
		return new Price($this->vat, $price);
	}

	private function getPriceByBasket(Basket $basket)
	{
		return $this->price;
	}

	public function setPrice($value, $withVat = FALSE)
	{
		$price = new Price($this->vat, $value, !$withVat);
		$this->price = $price->withoutVat;
		return $this;
	}
	
	public function addShipping(Shipping $shipping)
	{
		$this->shippings->add($shipping);
		return $this;
	}
	
	public function clearShippings()
	{
		$this->shippings->clear();
		return $this;
	}
	
	public function __toString()
	{
		return $this->name;
	}

}
