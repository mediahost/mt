<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PohodaItemRepository")
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
 * @property string $isInternet
 */
class PohodaItem extends BaseEntity
{

	const VALUE_VAT_HIGH = 'high';
	const VALUE_VAT_LOW = 'low';
	const VALUE_VAT_NONE = 'none';
	const VALUE_TRUE = 'true';

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
	
	/** @var int */
	protected $totalCount;

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $isSales;

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $isInternet;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $purchasingPrice; // Nákupní cena. Pokud není uvedena, bere se jako NULOVÁ. | Pokud není uveden atribut payVAT, jedná se o "Nákupní cena bez DPH".

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $purchasingRateVAT; // Sazba DPH pro nákup.

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $priceItem1; // Cena zásoby. ID = 1

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $sellingPrice; // Prodejní cena. Pokud není uvedena, bere se jako NULOVÁ. | Pokud není uveden atribut payVAT, jedná se o "Prodejní cena bez DPH".

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $sellingPriceWithVAT; // with VAT

	/** @ORM\Column(type="string", length=10, nullable=true) */
	protected $sellingRateVAT; // Sazba DPH pro prodej.

	/** @ORM\Column(type="float", nullable=true) */
	protected $recountedSellingWithoutVat;

	/** @ORM\Column(type="float", nullable=true) */
	protected $recountedSellingWithVat;

	public function __construct($id)
	{
		$this->id = $id;
		parent::__construct();
	}

	public function getName()
	{
		return (string) $this->name;
	}

	public function getEan()
	{
		return (string) $this->ean;
	}

	public function getCount()
	{
		return (int) $this->count;
	}

	public function getCountRecievedOrders()
	{
		return (int) $this->countReceivedOrders;
	}

	public function getTotalCount()
	{
		return $this->totalCount ? (int) $this->totalCount : $this->getCount();
	}

	public function getIsSales()
	{
		return ($this->isSales === self::VALUE_TRUE);
	}

	public function getIsInternet()
	{
		return ($this->isInternet === self::VALUE_TRUE);
	}

	public function getSellingRateVAT()
	{
		switch ($this->sellingRateVAT) {
			case self::VALUE_VAT_HIGH:
			case self::VALUE_VAT_LOW:
			case self::VALUE_VAT_NONE:
				return $this->sellingRateVAT;
			default:
				return self::VALUE_VAT_NONE;
		}
	}

	public function setRecountedSellingPrice($valueWithoutVat = NULL, $valueWithVat = NULL, $vatValue = 0)
	{
		$vat = new Vat(NULL, $vatValue);
		if ($valueWithoutVat) {
			$price = new Price($vat, $valueWithoutVat, TRUE);
		} else {
			$price = new Price($vat, $valueWithVat, FALSE);
		}
		$this->recountedSellingWithoutVat = $price->withoutVat;
		$this->recountedSellingWithVat = $price->withVat;
	}

	public function getPurchasingPriceWithoutVat()
	{
		return (float) $this->purchasingPrice;
	}

	public function getSellingPriceWithoutVat()
	{
		return (float) $this->sellingPrice;
	}

}
