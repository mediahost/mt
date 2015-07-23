<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property int $id
 * @property string $code
 * @property PohodaStorage $storage
 * @property string $name
 * @property string $ean
 * @property string $count
 * @property string $countReceivedOrders
 * @property string $purchasingPrice
 * @property string $sellingPrice
 * @property string $sellingPriceWithVAT
 * @property string $priceItem1
 * @property string $purchasingRateVAT
 * @property string $sellingRateVAT
 * @property string $isSales
 */
class PohodaItem extends BaseEntity
{

	use Model\Timestampable\Timestampable;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @var string
	 */
	protected $id;

	/** @ORM\Column(type="string", length=64) */
	protected $code;

	/** @ORM\Column(type="string", length=90, nullable=true) */
	protected $name;

	/** @ORM\ManyToOne(targetEntity="PohodaStorage", inversedBy="products", cascade="all") */
	protected $storage;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $ean;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $count;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $countReceivedOrders;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $purchasingPrice;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $priceItem1;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $sellingPrice;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $sellingPriceWithVAT;

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $purchasingRateVAT;

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $sellingRateVAT;

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $isSales;

	public function __construct($id)
	{
		$this->id = $id;
		parent::__construct();
	}

	public function __toString()
	{
		return (string) ($this->code . ':' . $this->value);
	}

}
