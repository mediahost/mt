<?php

namespace App\Components\Product\Form;

use App\ExchangeHelper;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;
use App\Model\Entity\Price;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class StockPrice extends StockBase
{

	const PERCENT_IS_PRICE = TRUE;

	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var ExchangeHelper @inject */
	public $exchangeHelper;

	/** @var bool */
	private $defaultWithVat = TRUE;

	/** @var bool */
	private $percentIsSale = self::PERCENT_IS_PRICE;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicHorizontalFormRenderer(4, 8));
		$form->getElementPrototype()->class('form-horizontal ajax');

		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findAll();

		$shopRepo = $this->em->getRepository(Shop::getClassName());
		$shops = $shopRepo->findAll();

		$defaultPrice = $this->defaultWithVat ? $this->stock->price->withVat : $this->stock->price->withoutVat;

		$form->addCheckSwitch('with_vat', 'Prices are with VAT', 'YES', 'NO')
			->setDefaultValue($this->defaultWithVat);
		$form->addText('price', 'Price')
			->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
			->setRequired();

		$fixed = $form->addContainer(Discount::FIXED_PRICE);
		$percents = $form->addContainer(Discount::PERCENTAGE);
		foreach ($groups as $group) {
			/* @var $group Group */
			$discount = $this->stock->getDiscountByGroup($group);
			if ($group->isBonusType()) {
				$placeholderPercentage = $group->percentage;
				$groupPrice = $discount && $discount->type === Discount::PERCENTAGE ? $this->stock->getPrice($group) : $group->getDiscountedPrice($this->stock->price);
				$placeholderPrice = $this->defaultWithVat ? $groupPrice->withVat : $groupPrice->withoutVat;
			} else {
				$placeholderPercentage = 0;
				$groupPrice = $this->defaultWithVat ? $this->stock->getPrice($group)->withVat : $this->stock->getPrice($group)->withoutVat;
				$placeholderPrice = $discount && $discount->type === Discount::PERCENTAGE ? $groupPrice : $defaultPrice;
			}

			$fixed->addText($group->id, $group->name)
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setAttribute('placeholder', $this->exchangeHelper->format($placeholderPrice));
			$percents->addText($group->id, $group->name)
				->setAttribute('class', ['mask_percentage', MetronicTextInputBase::SIZE_S])
				->setAttribute('placeholder', ($this->percentIsSale ? $placeholderPercentage : (100 - $placeholderPercentage)) . '%');
		}

		$shopSync = $form->addContainer('shopSync');
		$shopPrices = $form->addContainer('shopPrice');
		foreach ($shops as $shop) {
			foreach ($shop->variants as $variant) {
				/** @var ShopVariant $variant */
				if (!$variant->isDefault() && $variant->active) {
					$id = $shop->priceLetter . $variant->priceNumber;

					$nameSync = $this->translator->translate('Other for %shop%', NULL, ['shop' => $variant->fullName]);
					$shopSync->addCheckSwitch($id, $nameSync, 'YES', 'NO')
						->addCondition(Form::EQUAL, TRUE)
						->toggle('price_' . $id);

					switch ($variant->locale) {
						case 'cs':
							$currency = 'czk';
							break;
						case 'pl':
							$currency = 'pln';
							break;
						case 'sk':
						default:
							$currency = 'eur';
							break;
					}
					$defaultPrice = $this->stock->getDefaultPrice($shop->priceLetter, $variant->priceNumber);
					$defaultPriceValue = $this->defaultWithVat ? $defaultPrice->withVat : $defaultPrice->withoutVat;
					$formatedDefaultPrice = $this->exchangeHelper->format($defaultPriceValue, Strings::upper($currency), Strings::upper($currency));
					$shopPrices->addText($id)
						->setAttribute('class', ['mask_currency_' . Strings::lower($currency), MetronicTextInputBase::SIZE_S])
						->setAttribute('placeholder', $formatedDefaultPrice)
						->setOption('id', 'price_' . $id);
				}
			}
		}

		$shopVat = $form->addContainer('shopVat');
		foreach ($shops as $shop) {
			/** @var Shop $shop */
			$nameVat = $this->translator->translate('Vat for %shop%', NULL, ['shop' => $shop]);
			$shopVat->addSelect2($shop->priceLetter, $nameVat, $shop->vatValues)
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;
		}

		$form->addText('purchase', 'Purchase price without VAT')
			->setDisabled()
			->setOmitted(FALSE)
			->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
			->setOption('description', 'Editable only in Pohoda');

		$form->addSubmit('save', 'Save')
			->setAttribute('data-dismiss-after', 'true');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->stock);
	}

	private function load(ArrayHash $values)
	{
		$this->stock->setPurchasePrice($values->purchase > 0 ? $values->purchase : NULL);

		$vatRepo = $this->em->getRepository(Vat::getClassName());
		if (isset($values->shopVat)) {
			foreach ($values->shopVat as $priceLetter => $vatId) {
				$vat = $vatRepo->find($vatId);
				$this->stock->setVat($vat, $priceLetter);
			}
		}

		if (isset($values->shopSync) && isset($values->shopPrice)) {
			foreach ($values->shopSync as $shopId => $isSetted) {
				list($shopLetter, $shopNumber) = Stock::parseShopId($shopId);
				$this->stock->setSynchronizePrice($shopLetter, $shopNumber, !$isSetted);
				if ($isSetted) {
					$this->stock->setPrice($values->shopPrice[$shopId], NULL, $values->with_vat, $shopLetter, $shopNumber);
				}
			}
		}

		$fixed = $values->{Discount::FIXED_PRICE};
		$percents = $values->{Discount::PERCENTAGE};
		foreach ($fixed as $groupId => $fixedValue) {

			$fixedValue = Price::strToFloat($fixedValue);
			$discount = NULL;
			$percentValue = $percents->$groupId;
			if ($fixedValue > 0) {
				$fixedPrice = new Price($this->stock->vat, $fixedValue, !$values->with_vat);
				$discount = new Discount($fixedPrice->withoutVat, Discount::FIXED_PRICE);
			} else if ($percentValue &&
				0 < $percentValue && $percentValue < 100 &&
				(($this->percentIsSale && $percentValue > 0) || (!$this->percentIsSale && $percentValue < 100))
			) {
				$value = $this->percentIsSale ? $percentValue : (100 - $percentValue);
				$discount = new Discount($value, Discount::PERCENTAGE);
			}

			$this->loadDiscount($discount, $groupId);
		}
		$this->stock->setDefaultPrice($values->price, $values->with_vat);

		return $this;
	}

	private function loadDiscount($discount, $groupId)
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groupDiscountRepo = $this->em->getRepository(GroupDiscount::getClassName());

		/* @var $group Group */
		$group = $groupRepo->find($groupId);
		if (!$group) {
			return $this;
		}

		if ($discount) {
			$this->stock->addDiscount($discount, $group);
		} else if ($group->isBonusType()) {
			$this->stock->addDiscount($group->getDiscount(), $group);
		} else {
			$removedElements = $this->stock->removeDiscountsByGroup($group);
			foreach ($removedElements as $removedElement) {
				$groupDiscountRepo->delete($removedElement);
			}
		}

		return $this;
	}

	private function save()
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stockRepo->save($this->stock);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->stock->purchasePrice) {
			$values['purchase'] = $this->stock->purchasePrice->withoutVat;
		}
		if ($this->stock->price) {
			$values['price'] = $this->defaultWithVat ? $this->stock->price->withVat : $this->stock->price->withoutVat;
			$values['with_vat'] = $this->defaultWithVat;
		}
		$shopRepo = $this->em->getRepository(Shop::getClassName());
		foreach ($shopRepo->findAll() as $shop) {
			/** @var Shop $shop */
			$values['shopVat'][$shop->priceLetter] = $this->stock->getVat($shop->priceLetter)->id;
			foreach ($shop->variants as $variant) {
				/** @var ShopVariant $variant */
				$isSync = $this->stock->isSynchronizePrice($shop->priceLetter, $variant->priceNumber);
				$values['shopSync'][$variant->priceCode] = !$isSync;
				if (!$isSync) {
					$shopPrice = $this->stock->getPrice(NULL, $shop->priceLetter, $variant->priceNumber);
					$values['shopPrice'][$variant->priceCode] = $this->defaultWithVat ? $shopPrice->withVat : $shopPrice->withoutVat;
				}
			}
		}
		foreach ($this->stock->groupDiscounts as $groupDiscount) {
			/* @var $groupDiscount GroupDiscount */
			switch ($groupDiscount->discount->type) {
				case Discount::PERCENTAGE:
					$value = $this->percentIsSale ? $groupDiscount->discount->value : (100 - $groupDiscount->discount->value);
					break;
				default:
					$discountedPrice = $groupDiscount->discount->getDiscountedPrice($this->stock->price);
					$value = $this->defaultWithVat ? $discountedPrice->withVat : $discountedPrice->withoutVat;
					break;
			}
			$values[$groupDiscount->discount->type][$groupDiscount->group->id] = $value;
		}
		return $values;
	}

}

interface IStockPriceFactory
{

	/** @return StockPrice */
	function create();
}
