<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
 *
 * @property string $letter
 * @property ArrayCollection $variants
 * @property Address $address
 * @property BankAccount $bankAccounts
 * @property BankAccount $bankAccount1
 * @property BankAccount $bankAccount2
 */
class Shop extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=1) */
	protected $priceLetter;

	/** @ORM\OneToMany(targetEntity="ShopVariant", mappedBy="shop", cascade={"persist", "remove"}) */
	protected $variants;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}) */
	protected $address;

	/** @ORM\OneToOne(targetEntity="BankAccount", cascade={"persist", "remove"}) */
	protected $bankAccount1;

	/** @ORM\OneToOne(targetEntity="BankAccount", cascade={"persist", "remove"}) */
	protected $bankAccount2;

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

	public function addVariant(ShopVariant $variant)
	{
		$variant->shop = $this;
		$this->variants->add($variant);
		return $this;
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
