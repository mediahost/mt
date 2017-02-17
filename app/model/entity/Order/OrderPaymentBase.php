<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\MappedSuperclass()
 *
 * @property string $name
 */
class OrderPaymentBase extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\Column(type="float", nullable=true) */
	private $price;

	/** @ORM\Column(type="float", nullable=true) */
	private $vat;

	public function setPrice(Price $price)
	{
		$this->price = $price->withoutVat;
		$this->vat = $price->vat->value;
		return $this;
	}

	/** @return Price */
	public function getPrice()
	{
		$vat = $this->getVat();
		$price = new Price($vat, $this->price);
		return $price;
	}

	/** @return Vat */
	public function getVat()
	{
		return new Vat($this->vat ? $this->vat : 0);
	}
	
	public function __toString()
	{
		return $this->name;
	}

}
