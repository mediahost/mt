<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 *
 * @property string $locale
 * @property string $currency
 * @property Shop $shop
 * @property-read string $name
 * @property-read string $priceCode
 * @property-read string $fullName
 * @property int $priceNumber
 * @property ArrayCollection $shippings
 * @property ArrayCollection $payments
 */
class ShopVariant extends BaseEntity
{

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="Shop", inversedBy="variants") */
	protected $shop;

	/** @ORM\Column(type="smallint") */
	protected $priceNumber;

	/** @ORM\Column(type="string", length=2, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=3, nullable=true) */
	protected $currency;

	/** @ORM\OneToMany(targetEntity="Shipping", mappedBy="shopVariant") */
	protected $shippings;

	/** @ORM\OneToMany(targetEntity="Payment", mappedBy="shopVariant") */
	protected $payments;

	public function __construct($locale)
	{
		parent::__construct();
		$this->locale = $locale;
		$this->shippings = new ArrayCollection();
		$this->payments = new ArrayCollection();
	}

	public function getName()
	{
		return '#' . $this->id . ' - ' . $this->locale;
	}

	public function getPriceCode()
	{
		return $this->shop->priceLetter . $this->priceNumber;
	}

	public function getFullName()
	{
		return $this->shop . ' | ' . Strings::upper($this->locale);
	}

	public function isDefault()
	{
		return $this->priceNumber === Stock::DEFAULT_PRICE_VERSION && $this->shop->priceLetter === Stock::DEFAULT_PRICE_BASE;
	}

	public function __toString()
	{
		return $this->getName();
	}

}
