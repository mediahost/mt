<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PaymentRepository")
 *
 * @property bool $active
 * @property bool $needAddress
 * @property bool $useCond1
 * @property bool $useCond2
 * @property bool $isCard
 * @property bool $isHomecreditSk
 * @property string $name
 * @property string $html
 * @property string $errorHtml
 * @property Price $price
 * @property Price $freePrice
 * @property ArrayCollection $shippings
 * @property ShopVariant $shopVariant
 * @property string $currency
 */
class Payment extends BaseTranslatable
{

	use Model\Translatable\Translatable;

	/** @ORM\ManyToOne(targetEntity="ShopVariant", inversedBy="payments") */
	protected $shopVariant;

	/** @ORM\Column(type="boolean") */
	protected $active;

	/** @ORM\Column(type="boolean") */
	protected $useCond1 = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $useCond2 = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $needAddress = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $isCard = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $isOnDelivery = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $isHomecreditSk = FALSE;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\ManyToMany(targetEntity="Shipping", inversedBy="payments") */
	protected $shippings;

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
		$this->shippings = new ArrayCollection();
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
		$priceEntity = new Price($this->vat, $price, !$this->isPriceInPercent());
		return $priceEntity;
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
		$basket->shopVariant = $this->shopVariant;
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

	private function applyCond1($price, Basket $basket, $level = NULL)
	{
		return $price;
	}

	private function applyCond2($price, Basket $basket, $level = NULL)
	{
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

	public function addShipping(Shipping $shipping)
	{
		$this->shippings->add($shipping);
		return $this;
	}

	public function clearShippings()
	{
		$this->shippings->clear();
		return $this;
	}

	public function containShipping(Shipping $shipping)
	{
		return $this->shippings->contains($shipping);
	}

	public function getCurrency()
	{
		return $this->shopVariant->currency;
	}

	public function __toString()
	{
		return $this->name;
	}

}
