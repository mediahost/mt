<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ShippingRepository")
 *
 * @property bool $active
 * @property bool $useCond1
 * @property bool $useCond2
 * @property bool $needAddress
 * @property string $name
 * @property string $html
 * @property Price $price
 * @property Price $freePrice
 * @property ArrayCollection $payments
 */
class Shipping extends BaseTranslatable
{

	const SPECIAL_LIMIT = 50; // with VAT
	const SPECIAL_PRICE = 1.9; // with VAT

	use Model\Translatable\Translatable;

	/** @ORM\ManyToOne(targetEntity="ShopVariant", inversedBy="shippings") */
	protected $shopVariant;

	/** @ORM\Column(type="boolean") */
	protected $active;

	/** @ORM\Column(type="boolean") */
	protected $useCond1 = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $useCond2 = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $needAddress = TRUE;

	/** @ORM\Column(type="string", length=2, nullable=true) */
	protected $locality;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\ManyToMany(targetEntity="Payment", mappedBy="shippings") */
	protected $payments;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vat;

	/** @ORM\Column(type="float", nullable=true) */
	private $price;

	/** @ORM\Column(type="float", nullable=true) */
	private $percentPrice;

	/** @ORM\Column(type="float", nullable=true) */
	private $freePrice;

	public function __construct($currentLocale = NULL)
	{
		$this->payments = new ArrayCollection();
		parent::__construct($currentLocale);
	}

	private function getBasePrice()
	{
		if ($this->isPriceInPercent()) {
			return 0;
		}
		return $this->price;
	}

	public function getPrice(Basket $basket = NULL, $level = NULL)
	{
		$price = $basket ? $this->getPriceByBasket($basket, $level) : $this->getBasePrice();
		return new Price($this->vat, $price, !$this->isPriceInPercent());
	}

	public function getPercentPrice()
	{
		return $this->percentPrice;
	}

	private function getValueOfPercentPrice(Basket $basket, $level = NULL)
	{
		$basketPrice = $basket->getItemsTotalPrice(NULL, $level, TRUE);
		return $basketPrice * ($this->percentPrice / 100);
	}

	public function isPriceInPercent()
	{
		return (bool)$this->percentPrice;
	}

	public function getPriceByStocks(array $stocks, array $quantities = [])
	{
		$basket = new Basket();
		foreach ($stocks as $stock) {
			if ($stock instanceof Stock) {
				$quantity = array_key_exists($stock->id, $quantities) ? $quantities[$stock->id] : 1;
				$basket->setItem($stock, $quantity, FALSE);
			}
		}
		return $this->getPrice($basket);
	}

	private function getPriceByBasket(Basket $basket, $level = NULL)
	{
		$price = $this->getBasePrice();
		if ($this->isPriceInPercent()) {
			$price = $this->getValueOfPercentPrice($basket, $level);
		}
		if ($this->useCond1) {
			$price = $this->applyCond1($price, $basket, $level);
		}
		if ($this->useCond2) {
			$price = $this->applyCond2($price, $basket, $level);
		}
		return $this->applyFree($price, $basket, $level);
	}

	public function getFreePrice()
	{
		return new Price($this->vat, $this->freePrice);
	}

	/**
	 * Pokud je suma produktů ze speciální kategorie nižší než speciální limit,
	 * pak bude mít poštovné speciální cenu
	 * @param type $price
	 * @param Basket $basket
	 * @param type $level
	 * @return type
	 */
	private function applyCond1($price, Basket $basket, $level = NULL)
	{
		$specialLimit = new Price($this->vat, self::SPECIAL_LIMIT, FALSE);
		$specialPrice = new Price($this->vat, self::SPECIAL_PRICE, FALSE);
		if ($basket->hasItemInSpecialCategory()) {
			$specialSum = $basket->getSumOfItemsInSpecialCategory($level, TRUE);
			if ($specialSum <= $specialLimit->withVat && $this->price > $specialPrice->withVat) {
				$price = $specialPrice->withoutVat;
			}
		}
		return $price;
	}

	/**
	 * Pokud je suma produktů ze speciální kategorie vyšší než speciální limit,
	 * pak bude poštovné zdarma
	 * @param type $price
	 * @param Basket $basket
	 * @param type $level
	 * @return type
	 */
	private function applyCond2($price, Basket $basket, $level = NULL)
	{
		$specialLimit = new Price($this->vat, self::SPECIAL_LIMIT, FALSE);
		if ($basket->hasItemInSpecialCategory()) {
			$specialSum = $basket->getSumOfItemsInSpecialCategory($level, TRUE);
			if ($specialSum > $specialLimit->withVat) {
				$price = 0;
			}
		}
		return $price;
	}

	private function applyFree($price, Basket $basket, $level = NULL)
	{
		$freePrice = $this->getFreePrice();
		$freePriceVat = $freePrice->withVat;
		if ($freePriceVat > 0 && $basket->getItemsTotalPrice(NULL, $level, TRUE) > $freePriceVat) {
			$price = 0;
		}
		return $price;
	}

	public function setPrice($value, $withVat = FALSE)
	{
		$price = new Price($this->vat, $value, !$withVat);
		$this->price = $price->withoutVat;
		return $this;
	}

	public function setPercentPrice($value)
	{
		$this->percentPrice = $value;
		return $this;
	}

	public function setFreePrice($value, $withVat = FALSE)
	{
		$price = new Price($this->vat, $value, !$withVat);
		$this->freePrice = $price->withoutVat;
		return $this;
	}

	public function isAllowedPayment(Payment $payment)
	{
		return $this->payments->contains($payment);
	}

	public function isForLocality($locale)
	{
		return !$this->locality || $this->locality == $locale;
	}

	public function __toString()
	{
		return $this->name;
	}

}
