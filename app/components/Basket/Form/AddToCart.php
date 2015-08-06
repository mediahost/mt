<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Controls\TextInputBased\Spinner;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;

class AddToCart extends BaseControl
{

	const MAX = 100;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterAdd = [];

	// </editor-fold>

	/** @var Stock */
	private $stock;

	/** @var int */
	private $alreadyInBasket;

	/** @var int */
	private $canAddToBasket;

	/** @var int */
	private $max = self::MAX;

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

		$form->addSpinner('quantity')
				->setDisabled($this->isDisabled(1))
				->setPlusButton('default', 'fa fa-angle-up')
				->setMinusButton('default', 'fa fa-angle-down')
				->setMin(1)
				->setMax($this->max)
				->setType(Spinner::TYPE_UP_DOWN)
				->setSize(MetronicTextInputBase::SIZE_XS);

		$form->addSubmit('add', 'Add to cart')
				->setDisabled($this->isDisabled());

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		try {
			// TODO: add to cart
//			$basket->add($this->stock, $values->quantity);
			throw new \Exception;
			$this->onAfterAdd($values->quantity);
		} catch (\Exception $ex) {
			$message1 = $this->translator->translate('cart.product.onlyCountOnStore', $this->stock->inStore);
			$message2 = $this->translator->translate('cart.product.alreadyInBasket', $this->alreadyInBasket);
			$message3 = $this->canAddToBasket ?
					$this->translator->translate('cart.product.youCanAddOnly', $this->canAddToBasket) :
					$this->translator->translate('cart.product.youCannotAdd');
			$form->addError($message1);
			$form->addError($message2);
			$form->addError($message3);
		}
		$this->redrawControl();
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->stock) {
			throw new BaseControlException('Use setStock(\App\Model\Entity\Stock) before render');
		}
	}

	public function render()
	{
		$this->template->alreadyInBasket = $this->alreadyInBasket;
		parent::render();
	}

	// <editor-fold desc="setters & getters">

	public function setStock(Stock $stock)
	{
		$this->stock = $stock;
		$this->alreadyInBasket = 6; // TODO: load from basket
		$free = $this->stock->inStore - $this->alreadyInBasket;
		$this->canAddToBasket = $free > 0 ? $free : 0;
		$this->max = $this->canAddToBasket > self::MAX ? self::MAX : $this->canAddToBasket;
		return $this;
	}

	public function isDisabled($disabledValue = 0)
	{
		return $this->canAddToBasket <= $disabledValue;
	}

	// </editor-fold>
}

interface IAddToCartFactory
{

	/** @return AddToCart */
	function create();
}
