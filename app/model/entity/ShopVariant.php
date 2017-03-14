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
 * @property bool $active
 * @property Address $address
 * @property BankAccount $bankAccounts
 * @property BankAccount $bankAccount1
 * @property BankAccount $bankAccount2
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

	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}, fetch="EAGER") */
	protected $address;

	/** @ORM\OneToOne(targetEntity="BankAccount", cascade={"persist", "remove"}, fetch="EAGER") */
	protected $bankAccount1;

	/** @ORM\OneToOne(targetEntity="BankAccount", cascade={"persist", "remove"}, fetch="EAGER") */
	protected $bankAccount2;

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

	public function getFullName($currency = TRUE)
	{
		return $this->shop . ' | ' . Strings::upper($currency ? $this->currency : $this->locale);
	}

	public function isDefault()
	{
		return $this->priceNumber === Stock::DEFAULT_PRICE_VERSION && $this->shop->priceLetter === Stock::DEFAULT_PRICE_BASE;
	}

	public function getAddress($raw = FALSE)
	{
		if ($raw) {
			return $this->address;
		}
		$importedAddress = clone $this->shop->address;
		if ($this->address) {
			$importedAddress->import($this->address);
		}
		return $importedAddress;
	}

	public function getBankAccount1($raw = FALSE)
	{
		if ($raw) {
			return $this->bankAccount1;
		}

		if ($this->shop->bankAccount1) {
			$importedAccount = clone $this->shop->bankAccount1;
			if ($this->bankAccount1) {
				$importedAccount->import($this->bankAccount1);
			}
			return $importedAccount;
		} else {
			return $this->bankAccount1;
		}
	}

	public function getBankAccount2($raw = FALSE)
	{
		if ($raw) {
			return $this->bankAccount2;
		}

		if ($this->shop->bankAccount2) {
			$importedAccount = clone $this->shop->bankAccount2;
			if ($this->bankAccount2) {
				$importedAccount->import($this->bankAccount2);
			}
			return $importedAccount;
		} else {
			return $this->bankAccount2;
		}
	}

	public function getBankAccounts($raw = FALSE)
	{
		$accounts = new ArrayCollection();
		$accounts->add($this->getBankAccount1($raw));
		$accounts->add($this->getBankAccount2($raw));
		return $accounts;
	}

	public function getReplacementTags()
	{
		$address = $this->getAddress();
		return [
			'%mail_1%' => $address->mail,
			'%mail_2%' => $address->mailHome,
			'%phone_number_1%' => $address->phone,
			'%phone_number_2%' => $address->phoneHome,
		];
	}

	public function __toString()
	{
		return $this->getFullName();
	}

}
