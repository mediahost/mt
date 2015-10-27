<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\Html;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property string $street
 * @property string $city
 * @property string $zipcode
 * @property string $country
 * @property string $countryFormat
 * @property string $phone
 * @property string $ico
 * @property string $icoVat
 * @property string $dic
 * @property string $note
 */
class Address extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $name;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $street;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $city;

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $zipcode;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $country;

	/** @ORM\Column(type="string", length=30, nullable=true) */
	protected $phone;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $ico;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $icoVat;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $dic;

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $note;
	
	public function import(Address $address, $force = FALSE)
	{
		if ($force || $address->name) {
			$this->name = $address->name;
		}
		if ($force || $address->street) {
			$this->street = $address->street;
		}
		if ($force || $address->city) {
			$this->city = $address->city;
		}
		if ($force || $address->zipcode) {
			$this->zipcode = $address->zipcode;
		}
		if ($force || $address->country) {
			$this->country = $address->country;
		}
		if ($force || $address->phone) {
			$this->phone = $address->phone;
		}
		if ($force || $address->ico) {
			$this->ico = $address->ico;
		}
		if ($force || $address->dic) {
			$this->dic = $address->dic;
		}
		if ($force || $address->icoVat) {
			$this->icoVat = $address->icoVat;
		}
		if ($force || $address->note) {
			$this->note = $address->note;
		}
	}

	public function isComplete()
	{
		return $this->name &&
				$this->street &&
				$this->city &&
				$this->zipcode &&
				$this->country &&
				$this->phone;
	}

	public function isCompany()
	{
		return $this->ico ||
				$this->dic ||
				$this->icoVat;
	}

	public function clearCompany()
	{
		$this->ico = NULL;
		$this->icoVat = NULL;
		$this->dic = NULL;
		return $this;
	}

	public function isFilled()
	{
		return $this->name || $this->street || $this->city || $this->zipcode;
	}

	public function __toString()
	{
		return (string) $this->name;
	}
	
	public function getCityFormat()
	{
		return Helpers::concatStrings(' ', $this->zipcode, $this->city);
	}
	
	public function getCountryFormat()
	{
		return $this->getCountry(TRUE);
	}
	
	public function getCountry($formated = FALSE)
	{
		if ($formated) {
			$countries = self::getCountries();
			if (array_key_exists($this->country, $countries)) {
				return $countries[$this->country];
			}
		}
		return $this->country;
	}
	
	public function format()
	{
		$lineSeparator = Html::el('br');
		$name = $this->name;
		$street = $this->street;
		$city = $this->getCityFormat();
		$country = $this->getCountryFormat();
		$address = Helpers::concatStrings($lineSeparator, $name, $street, $city, $country);
		return $address;
	}

	public static function getCountries()
	{
		$countries = [
			'SK' => 'Slovenská republika',
			'CZ' => 'Česká republika',
			'AT' => 'Austria',
			'BE' => 'Belgium',
			'BG' => 'Bulgaria',
			'HR' => 'Croatia',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'HU' => 'Hungary',
			'IE' => 'Ireland',
			'IT' => 'Italy',
			'LV' => 'Latvia',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'NL' => 'Netherlands',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'SI' => 'Slovenia',
			'ES' => 'Spain',
			'SE' => 'Sweden',
			'GB' => 'United Kingdom',
		];
		return $countries;
	}

}
