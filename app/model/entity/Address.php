<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property string $street
 * @property string $city
 * @property string $zipcode
 * @property string $country
 * @property string $phone
 * @property string $ico
 * @property string $icoVat
 * @property string $dic
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
		return (bool) $this->name;
	}

	public function __toString()
	{
		return (string) $this->name;
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
