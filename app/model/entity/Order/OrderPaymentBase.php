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
		$vat = new Vat(NULL, $this->vat);
		return new Price($vat, $this->price);
	}

}
