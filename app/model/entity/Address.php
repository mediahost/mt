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
 */
class Address extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=512, nullable=false) */
	protected $name;

	/** @ORM\Column(type="string", length=512, nullable=false) */
	protected $street;

	/** @ORM\Column(type="string", length=512, nullable=false) */
	protected $city;

	/** @ORM\Column(type="string", length=512, nullable=false) */
	protected $zipcode;

	/** @ORM\Column(type="string", length=512, nullable=false) */
	protected $country;

	// TODO: do this method
	public function parseFromText($text)
	{
		return $this;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
