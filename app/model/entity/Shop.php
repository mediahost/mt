<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\Strings;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
 *
 * @property string $priceLetter
 * @property ArrayCollection $variants
 * @property ArrayCollection $vats
 * @property array $vatValues
 * @property Address $address
 * @property BankAccount $bankAccounts
 * @property BankAccount $bankAccount1
 * @property BankAccount $bankAccount2
 */
class Shop extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=1) */
	private $priceLetter;

	/** @ORM\OneToMany(targetEntity="ShopVariant", mappedBy="shop", cascade={"persist", "remove"}) */
	protected $variants;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}) */
	protected $address;

	/** @ORM\OneToOne(targetEntity="BankAccount", cascade={"persist", "remove"}) */
	protected $bankAccount1;

	/** @ORM\OneToOne(targetEntity="BankAccount", cascade={"persist", "remove"}) */
	protected $bankAccount2;

	/** @ORM\OneToMany(targetEntity="Vat", mappedBy="shop") */
	protected $vats;

	/** @ORM\Column(type="simple_array", nullable=true) */
	private $deniedLocales;

	/** @ORM\Column(type="simple_array", nullable=true) */
	private $deniedCurrencies;

	public function getBankAccounts()
	{
		$accounts = new ArrayCollection();
		$accounts->add($this->bankAccount1);
		$accounts->add($this->bankAccount2);
		return $accounts;
	}

	public function getVariant($locale)
	{
		$variant = NULL;
		$hasVariant = function ($key, ShopVariant $item) use (&$variant, $locale) {
			if ($item->locale === $locale) {
				$variant = $item;
				return TRUE;
			}
			return FALSE;
		};
		$this->variants->exists($hasVariant);
		return $variant;
	}

	public function getPriceLetter()
	{
		return Strings::upper($this->priceLetter);
	}

	public function setPriceLetter($value)
	{
		$this->priceLetter = Strings::upper($value);
	}

	public function addVariant(ShopVariant $variant)
	{
		$variant->shop = $this;
		$this->variants->add($variant);
		return $this;
	}

	public function getVatValues()
	{
		$values = [];
		foreach ($this->vats as $vat) {
			$values[$vat->id] = (string)$vat;
		}
		return $values;
	}

	public function isLocaleDenied($locale)
	{
		return is_array($this->deniedCurrencies) && in_array($locale, $this->deniedLocales);
	}

	public function isCurrencyDenied($currency)
	{
		return is_array($this->deniedCurrencies) && in_array($currency, $this->deniedCurrencies);
	}

	public function __construct($letter)
	{
		parent::__construct();
		$this->priceLetter = $letter;
		$this->variants = new ArrayCollection();
	}

	public function __toString()
	{
		return (string)$this->address;
	}

}
