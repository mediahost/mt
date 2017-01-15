<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $locale
 * @property Shop $shop
 */
class ShopVariant extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=2) */
	protected $locale;

	/** @ORM\ManyToOne(targetEntity="Shop", inversedBy="variants") */
	protected $shop;

	public function __construct($locale)
	{
		parent::__construct();
		$this->locale = $locale;
	}

	public function __toString()
	{
		return '#' . $this->id . ' - ' . $this->locale;
	}

}
