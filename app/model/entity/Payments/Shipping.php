<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ShippingRepository")
 *
 * @property bool $active
 * @property bool $needAddress
 * @property string $name
 * @property Price $price
 * @property ArrayCollection $payments
 */
class Shipping extends BaseEntity
{

	const PERSONAL = 1;
	const CZECH_POST = 2;
	const SLOVAK_POST = 3;
	const PPL = 4;

	use Identifier;

	/** @ORM\Column(type="boolean") */
	protected $active;

	/** @ORM\Column(type="boolean") */
	protected $needAddress;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\ManyToMany(targetEntity="Payment", mappedBy="shippings") */
	protected $payments;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vat;

	/** @ORM\Column(type="float", nullable=true) */
	private $price;

	public function __construct()
	{
		$this->payments = new ArrayCollection();
		parent::__construct();
	}

	public function getPrice(Basket $basket = NULL, $level = NULL)
	{
		$price = $basket ? $this->getPriceByBasket($basket, $level) : $this->price;
		return new Price($this->vat, $price);
	}

	private function getPriceByBasket(Basket $basket, $level = NULL)
	{
		return $this->price;
	}

	public function setPrice($value, $withVat = FALSE)
	{
		$price = new Price($this->vat, $value, !$withVat);
		$this->price = $price->withoutVat;
		return $this;
	}

	public function __toString()
	{
		return $this->name;
	}

}
