<?php

namespace App\Components\Order\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Components\Basket\Form\AddToCart;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Controls\TextInputBased\Spinner;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Order;
use App\Model\Entity\Stock;
use App\Model\Facade\Exception\InsufficientQuantityException;
use Nette\Utils\ArrayHash;

class OrderProductsEdit extends BaseControl
{

	/** @var Order */
	private $order;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$quantities = $form->addContainer('quantities');
		foreach ($this->order->items as $item) {
			$item->stock->product->setCurrentLocale($this->lang);
			$quantities->addSpinner($item->stock->id, $item->stock->product)
					->setDefaultValue($item->quantity)
					->setPlusButton('default', 'fa fa-angle-up')
					->setMinusButton('default', 'fa fa-angle-down')
					->setMin(0)
					->setMax(AddToCart::MAX)
					->setType(Spinner::TYPE_UP_DOWN)
					->setSize(MetronicTextInputBase::SIZE_XS);
		}

		$form->addMultiSelect2('new', 'Add new items')
				->setAutocomplete('autocompleteProducts');

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->order);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		foreach ($values->quantities as $stockId => $quantity) {
			try {
				$this->addOrderItem($stockId, $quantity);
			} catch (InsufficientQuantityException $e) {
				$form['quantities'][$stockId]->addError('Insufficient quantity of this product on stock.');
			}
		}
		foreach ($values->new as $stockId) {
			try {
				$this->addOrderItem($stockId, 1);
			} catch (InsufficientQuantityException $e) {
				$message = $this->translator->translate('No free product with ID \'%number%\'.', ['number' => $stockId]);
				$form['new']->addError($message);
			}
		}
		
		return $this;
	}

	private function addOrderItem($stockId, $quantity)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		/* @var $stock Stock */
		$stock = $stockRepo->find($stockId);

		if ($stock) {
			$priceLevel = ($this->order->user && $this->order->user->group) ? $this->order->user->group->level : NULL;
			$price = $stock->getPrice($priceLevel);
			$locale = $this->order->locale;
			$this->order->setItem($stock, $price, $quantity, $locale);
		}
	}

	private function save()
	{
		$orderRepo = $this->em->getRepository(Order::getClassName());
		$orderRepo->save($this->order);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->order) {
			throw new BaseControlException('Use setOrder(\App\Model\Entity\Order) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setOrder(Order $order)
	{
		$this->order = $order;
		return $this;
	}

	// </editor-fold>
}

interface IOrderProductsEditFactory
{

	/** @return OrderProductsEdit */
	function create();
}
