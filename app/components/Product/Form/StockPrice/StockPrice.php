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
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;

class StockPrice extends StockBase
{
	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var ExchangeHelper @inject */
	public $exchangeHelper;

	/** @var bool */
	private $defaultWithVat = TRUE;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicHorizontalFormRenderer());

		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findAll();

		$defaultPrice = $this->defaultWithVat ? $this->stock->price->withVat : $this->stock->price->withoutVat;

		$form->addCheckSwitch('with_vat', 'Prices are with VAT', 'YES', 'NO')
				->setDefaultValue(FALSE);
		$form->addText('price', 'Price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setRequired();

		$fixed = $form->addContainer(Discount::FIXED_PRICE);
		$percents = $form->addContainer(Discount::PERCENTAGE);
		foreach ($groups as $group) {
			$groupPrice = $this->defaultWithVat ? $this->stock->getPrice($group)->withVat : $this->stock->getPrice($group)->withoutVat;
			$discount = $this->stock->getDiscountByGroup($group);
			$placeholderPrice = $discount && $discount->type === Discount::PERCENTAGE ? $groupPrice : $defaultPrice;

			$fixed->addText($group->id, $group->name)
					->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
					->setAttribute('placeholder', $this->exchangeHelper->format($placeholderPrice));
			$percents->addText($group->id, $group->name)
					->setAttribute('class', ['mask_percentage', MetronicTextInputBase::SIZE_S])
					->setAttribute('placeholder', '100%');
		}

		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addText('purchase', 'Purchase price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);
		$form->addText('old', 'Old price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);

		$form->addSubmit('save', 'Save');

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
		$this->stock->setPurchasePrice($values->purchase > 0 ? $values->purchase : NULL, $values->with_vat);
		$this->stock->setOldPrice($values->old > 0 ? $values->old : NULL, $values->with_vat);

		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find($values->vat);
		$this->stock->vat = $vat;

		$fixed = $values->{Discount::FIXED_PRICE};
		$percents = $values->{Discount::PERCENTAGE};
		foreach ($fixed as $groupId => $fixedValue) {

			$fixedValue = Price::strToFloat($fixedValue);
			$discount = NULL;
			if ($fixedValue > 0) {
				$discount = new Discount($fixedValue, Discount::FIXED_PRICE);
			} else if ($percents->$groupId &&
					0 < $percents->$groupId && $percents->$groupId <= 100) {
				$discount = new Discount(100 - $percents->$groupId, Discount::PERCENTAGE);
			}

			$this->loadDiscount($discount, $groupId);
		}
		$this->stock->setDefaltPrice($values->price, $values->with_vat);

		return $this;
	}

	private function loadDiscount($discount, $groupId)
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groupDiscountRepo = $this->em->getRepository(GroupDiscount::getClassName());

		$group = $groupRepo->find($groupId);
		if (!$group) {
			return $this;
		}

		if ($discount) {
			$this->stock->addDiscount($discount, $group);
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
			$values += [
				'purchase' => $this->defaultWithVat ? $this->stock->purchasePrice->withVat : $this->stock->purchasePrice->withoutVat,
			];
		}
		if ($this->stock->oldPrice) {
			$values += [
				'old' => $this->defaultWithVat ? $this->stock->oldPrice->withVat : $this->stock->oldPrice->withoutVat,
			];
		}
		if ($this->stock->price) {
			$values += [
				'price' => $this->defaultWithVat ? $this->stock->price->withVat : $this->stock->price->withoutVat,
				'with_vat' => $this->defaultWithVat,
				'vat' => $this->stock->price->vat->id,
			];
		}
		foreach ($this->stock->groupDiscounts as $groupDiscount) {
			/* @var $groupDiscount GroupDiscount */
			switch ($groupDiscount->discount->type) {
				case Discount::PERCENTAGE:
					$value = 100 - $groupDiscount->discount->value;
					break;
				default:
					$value = $groupDiscount->discount->value;
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
