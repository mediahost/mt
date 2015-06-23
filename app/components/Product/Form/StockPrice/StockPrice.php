<?php

namespace App\Components\Product\Form;

use App\ExchangeHelper;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;
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

		$form = new Form;
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
			$placeholderPrice = $discount->type === Discount::PERCENTAGE ? $groupPrice : $defaultPrice;
			
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
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setOption('description', 'Vat included');
		$form->addText('old', 'Old price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setOption('description', 'Vat included');

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
		$this->stock->purchasePrice = $values->purchase > 0 ? $values->purchase : NULL;
		$this->stock->oldPrice = $values->old > 0 ? $values->old : NULL;

		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find($values->vat);
		$this->stock->vat = $vat;

		$groupRepo = $this->em->getRepository(Group::getClassName());
		
		$fixed = $values->{Discount::FIXED_PRICE};
		$percents = $values->{Discount::PERCENTAGE};
		foreach ($fixed as $groupId => $fixedValue) {
			if ($fixedValue > 0) {
				$discount = new Discount($fixedValue, Discount::FIXED_PRICE);
			} else if ($percents->$groupId &&
					0 < $percents->$groupId && $percents->$groupId <= 100) {
				$discount = new Discount($percents[$groupId], Discount::PERCENTAGE);
			} else {
				continue;
			}
			$group = $groupRepo->find($groupId);
			if ($group) {
				$this->stock->addDiscount($discount, $group);
			}
		}
		$this->stock->setDefaltPrice($values->price, $values->with_vat);

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
		$values = [
			'purchase' => $this->stock->purchasePrice,
			'old' => $this->stock->oldPrice,
		];
		if ($this->stock->price) {
			$values += [
				'price' => $this->defaultWithVat ? $this->stock->price->withVat : $this->stock->price->withoutVat,
				'with_vat' => $this->defaultWithVat,
				'vat' => $this->stock->price->vat->id,
			];
		}
		foreach ($this->stock->groupDiscounts as $groupDiscount) {
			/* @var $groupDiscount GroupDiscount */
			$values[$groupDiscount->discount->type][$groupDiscount->group->id] = $groupDiscount->discount->value;
		}
		return $values;
	}

}

interface IStockPriceFactory
{

	/** @return StockPrice */
	function create();
}
