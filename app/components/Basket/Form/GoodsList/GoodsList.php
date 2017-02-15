<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\Exception\BasketFacadeException;
use App\Model\Facade\Exception\InsufficientQuantityException;

class GoodsList extends BaseControl
{

	const MAX = 100;
	const MIN = 1;

	/** @var BasketFacade @inject */
	public $basketFacade;

	// <editor-fold desc="events">

	/** @var array */
	public $onSend = [];

	// </editor-fold>

	/** @var int */
	protected $priceLevel;

	/** @var bool */
	protected $allowDiscount = TRUE;

	/** @var ShopVariant */
	protected $shopVariant;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = [
			!$this->isSendOnChange ?: 'sendOnChange',
			!$this->isAjax ?: 'ajax'
		];

		$quantityContainer = $form->addContainer('quantity');
		foreach ($this->basketFacade->getItems() as $item) {
			$quantityContainer->addText($item->stock->id)
				->setValue($item->quantity)
				->setType('number')
				->addRule(Form::INTEGER, 'cart.wrongValue')
				->addRule(Form::RANGE, 'cart.quantityRange', [self::MIN, $item->stock->inStore]);
		}

		$form->addText('voucher', 'cart.voucher.code')
			->setAttribute('placeholder', 'cart.voucher.code')
			->getControlPrototype()->class[] = 'noSendOnChange';
		$form->addSubmit('insert', 'cart.voucher.insert');

		$form->addSubmit('send', 'cart.continue')
			->setDisabled(!$this->basketFacade->isAllItemsInStore());

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formValidate(Form $form, $values)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		foreach ($values->quantity as $stockId => $quantity) {
			$stock = $stockRepo->find($stockId);
			if ($stock && isset($form['quantity'][$stockId])) {
				if ($stock->inStore < $quantity) {
					$errorMessage = $this->translator->translate('cart.countInStore', ['count' => $stock->inStore, 'unit' => $stock->product->unit]);
					$form['quantity'][$stockId]->addError($errorMessage);
				}
			}
		}
		if ($this->presenter->ajax) {
			$this->presenter->redrawControl();
		}
	}

	public function formSucceeded(Form $form, $values)
	{
		if ($form['insert']->submittedBy) {
			try {
				$this->basketFacade->addVoucher($values->voucher);
				$this->presenter->flashMessage($this->translator->translate('cart.voucher.wasAdded'), 'success');
			} catch (BasketFacadeException $ex) {
				$this->presenter->flashMessage($this->translator->translate($ex->getMessage()), 'danger');
			}
			$form['voucher']->setValue(NULL);
			if ($this->presenter->ajax) {
				$this->presenter->redrawControl();
			}
			return;
		}

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		foreach ($values->quantity as $stockId => $quantity) {
			$stock = $stockRepo->find($stockId);
			if ($stock && isset($form['quantity'][$stockId])) {
				try {
					$this->basketFacade->setQuantity($stock, $quantity);
				} catch (InsufficientQuantityException $e) {
					$errorMessage = $this->translator->translate('cart.countInStore', ['count' => $stock->inStore, 'unit' => $stock->product->unit]);
					$form['quantity'][$stockId]->addError($errorMessage);
					$form['quantity'][$stockId]->setValue($stock->inStore);
				}
			}
		}

		if (!$form->hasErrors() && $form['send']->submittedBy) {
			$this->onSend();
		} else {
			if ($this->presenter->ajax) {
				$this->presenter->redrawControl();
			}
		}
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	public function render()
	{
		$this->template->basket = $this->basketFacade;
		$this->template->priceLevel = $this->priceLevel;
		$this->template->exchange = $this->exchange;
		$currency = $this->exchange[$this->exchange->getWeb()];
		$this->template->currencySymbol = $currency->getFormat()->getSymbol();
		$this->template->allowDiscount = $this->allowDiscount;
		parent::render();
	}

	// <editor-fold desc="setters & getters">

	public function setPriceLevel($level)
	{
		$this->priceLevel = $level;
		return $this;
	}

	public function setShopVariant(ShopVariant $variant)
	{
		$this->shopVariant = $variant;
		return $this;
	}

	public function setAllowDiscount($allowDiscount = TRUE)
	{
		$this->allowDiscount = $allowDiscount;
		return $this;
	}

	// </editor-fold>
}

interface IGoodsListFactory
{

	/** @return GoodsList */
	function create();
}
