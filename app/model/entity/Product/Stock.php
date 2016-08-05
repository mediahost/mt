<?php

namespace App\Model\Entity;

use App\Model\Entity\Traits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\StockRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\StockListener"})
 *
 * @property Product $product
 * @property Variant $variant1
 * @property Variant $variant2
 * @property Variant $variant3
 * @property Price $price
 * @property array $groupDiscounts
 * @property Price $purchasePrice
 * @property Price $oldPrice
 * @property Vat $vat
 * @property mixed $createdBy
 * @property mixed $updatedBy
 * @property mixed $deletedBy
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DateTime $deletedAt
 * @property DateTime $updatedPohodaDataAt
 * @property boolean $active
 * @property int $quantity
 * @property int $lock
 * @property int $inStore
 * @property string $barcode
 * @property string $gift
 * @property string $pohodaCode
 * @property string $importedFrom
 */
class Stock extends BaseEntity
{

	use Identifier;
	use Model\Blameable\Blameable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;
	use Traits\StockPrices;
	use Traits\StockQuantities;

	/** @ORM\ManyToOne(targetEntity="Product", inversedBy="stocks", cascade={"persist"}) */
	protected $product;

	/** @ORM\ManyToOne(targetEntity="Variant") */
	protected $variant1;

	/** @ORM\ManyToOne(targetEntity="Variant") */
	protected $variant2;

	/** @ORM\ManyToOne(targetEntity="Variant") */
	protected $variant3;

	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $barcode;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $gift;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $pohodaCode;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $importedFrom;

	/** @ORM\Column(type="datetime", nullable=true) */
	protected $updatedPohodaDataAt;

	/** @ORM\OneToMany(targetEntity="WatchDog", mappedBy="stock") */
	protected $watchDogs;

	/** @ORM\OneToMany(targetEntity="Visit", mappedBy="stock") */
	protected $visits;

	public function __construct()
	{
		$this->product = new Product();
		$this->groupDiscounts = new ArrayCollection();
		$this->watchDogs = new ArrayCollection();
		$this->visits = new ArrayCollection();
		parent::__construct();
	}

	public function __toString()
	{
		return (string)$this->product;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function setActive($value = TRUE)
	{
		$this->active = $value;
		$this->product->active = $value;
	}

	public function setChangePohodaData($time = 'now')
	{
		$datetime = $time instanceof DateTime ? $time : DateTime::from($time);
		$this->updatedPohodaDataAt = $datetime;
		return $this;
	}

	public function getUrlId()
	{
		return $this->product->getUrlId();
	}

	public function getSlug()
	{
		return $this->product->getSlug();
	}

	public function &__get($name)
	{
		if (preg_match('/^price(\d+)$/', $name, $matches)) {
			$value = $this->getPrice($matches[1]);
			return $value;
		} else {
			return parent::__get($name);
		}
	}

}
