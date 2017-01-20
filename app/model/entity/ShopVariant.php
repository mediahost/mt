<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 *
 * @property string $locale
 * @property Shop $shop
 * @property-read string $name
 * @property-read string $fullName
 * @property int $priceNumber
 */
class ShopVariant extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=2) */
	protected $locale;

	/** @ORM\ManyToOne(targetEntity="Shop", inversedBy="variants") */
	protected $shop;

	/** @ORM\Column(type="smallint") */
	protected $priceNumber;

	public function __construct($locale)
	{
		parent::__construct();
		$this->locale = $locale;
	}

	public function getName()
	{
		return '#' . $this->id . ' - ' . $this->locale;
	}

	public function getFullName()
	{
		return $this->shop . ' | ' . Strings::upper($this->locale);
	}

	public function __toString()
	{
		return $this->getName();
	}

}
