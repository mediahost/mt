<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\Html;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 *
 * @property string $number
 * @property string $fullNumber
 * @property string $code
 * @property string $iban
 * @property string $swift
 * @property string $country
 * @property string $countryFormat
 */
class BankAccount extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $number;

	/** @ORM\Column(type="string", length=5, nullable=true) */
	protected $code;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $iban;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $swift;

	/** @ORM\Column(type="string", length=2, nullable=true) */
	protected $country;

	public function getFullNumber()
	{
		return $this->number . '/' . $this->code;
	}

	public function getCountryFormat()
	{
		return $this->getCountry(TRUE);
	}

	public function getCountry($formated = FALSE)
	{
		if ($formated) {
			$countries = Address::getCountries();
			if (array_key_exists($this->country, $countries)) {
				return $countries[$this->country];
			}
		}
		return $this->country;
	}

	public function import(BankAccount $account, $force = FALSE)
	{
		if ($force || $account->number) {
			$this->number = $account->number;
		}
		if ($force || $account->code) {
			$this->code = $account->code;
		}
		if ($force || $account->iban) {
			$this->iban = $account->iban;
		}
		if ($force || $account->swift) {
			$this->swift = $account->swift;
		}
		if ($force || $account->country) {
			$this->country = $account->country;
		}
	}

	public function __toString()
	{
		return (string) $this->getFullNumber();
	}

}
