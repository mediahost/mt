<?php

namespace App\Components\Order\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Components\Basket\Form\AddToCart;
use App\ExchangeHelper;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Controls\TextInputBased\Spinner;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Order;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\OrderFacade;
use Nette\Utils\ArrayHash;

class OrderProductsEdit extends BaseControl
{

	/** @var Order */
	private $order;

	/** @var OrderFacade @inject */
	public $orderFacade;

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

		$disabled = !$this->order->isEditable;

		$quantities = $form->addContainer('quantities');
		foreach ($this->order->items as $item) {
			$item->stock->product->setCurrentLocale($this->lang);
			$quantities->addSpinner($item->stock->id, $item->stock->product)
					->setDisabled($disabled)
					->setDefaultValue($item->quantity)
					->setPlusButton('default', 'fa fa-angle-up')
					->setMinusButton('default', 'fa fa-angle-down')
					->setMin(0)
					->setMax(AddToCart::MAX)
					->setType(Spinner::TYPE_UP_DOWN)
					->setSize(MetronicTextInputBase::SIZE_XS);
		}

		$shippings = [NULL => 'payments.form.select'];
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$shippings += $shippingRepo->findPairs([
			'active' => TRUE,
			'shopVariant' => $this->order->shopVariant,
		], 'name');
		$form->addSelect2('shipping', 'Shipping', $shippings)
						->setDisabled($disabled)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;

		$payments = [NULL => 'payments.form.select'];
		$paymentRepo = $this->em->getRepository(Payment::getClassName());
		$payments += $paymentRepo->findPairs([
			'active' => TRUE,
			'shopVariant' => $this->order->shopVariant,
		], 'name');
		$form->addSelect2('payment', 'Payment', $payments)
						->setDisabled($disabled)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;

		if (!$disabled) {
			$form->addMultiSelect2('new', 'Add new items')
					->setAutocomplete('autocompleteStocks');
		}

		$form->addSubmit('save', 'Save')
				->setDisabled($disabled);

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$oldOrderItems = [];
		foreach ($this->order->items as $key => $item) {
			$oldOrderItems[$key] = clone $item;
		}
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->orderFacade->onOrderChangeProducts($this->order, $oldOrderItems);
			$this->onAfterSave($this->order);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		foreach ($values->quantities as $stockId => $quantity) {
			try {
				$this->setOrderItem($stockId, $quantity);
			} catch (InsufficientQuantityException $e) {
				$stockRepo = $this->em->getRepository(Stock::getClassName());
				/** @var Stock $stock */
				$stock = $stockRepo->find($stockId);
				if ($stock) {
					$quantityInOrder = $this->order->getItemCount($stock);
					$repairedQuantity = $quantityInOrder + $stock->inStore;
					if ($stock->inStore) {
						$message = $this->translator->translate('Only %count% free product.', $stock->inStore);
						$message .= ' ';
						$message .= $this->translator->translate('%count% item was already inserted.', $quantityInOrder);
					} else {
						$message = $this->translator->translate('No more free product.');
					}
					$form['quantities'][$stockId]->setValue($repairedQuantity);
				} else {
					$message = $this->translator->translate('Insufficient quantity on stock.');
				}
				$form['quantities'][$stockId]->addError($message);
			}
		}
		if ($this->order->isEditable) {
			foreach ($values->new as $stockId) {
				try {
					$this->setOrderItem($stockId, 1, TRUE);
				} catch (InsufficientQuantityException $e) {
					$stockRepo = $this->em->getRepository(Stock::getClassName());
					$stock = $stockRepo->find($stockId);
					if ($stock) {
						$message = $this->translator->translate('No free product \'%name%\'.', ['name' => $stock->product]);
					} else {
						$message = $this->translator->translate('No free product with ID \'%number%\'.', ['number' => $stockId]);
					}
					$form['new']->addError($message);
				}
			}
		}
		$stocks = [];
		$quantities = [];
		foreach ($this->order->items as $orderItem) {
			$stocks[] = $orderItem->stock;
			$quantities[$orderItem->stock->id] = $orderItem->quantity;
		}
		if ($values->shipping) {
			$shippingRepo = $this->em->getRepository(Shipping::getClassName());
			$shipping = $shippingRepo->find($values->shipping);
			if ($shipping) {
				$originalPrice = $shipping->price;
				$customPrice = $shipping->getPriceByStocks($stocks, $quantities);
				$shipping->setPrice($customPrice->withoutVat, FALSE);
				$this->order->shipping = $shipping;
				$shipping->price = $originalPrice->withoutVat;
			}
		}
		if ($values->payment) {
			$paymentRepo = $this->em->getRepository(Payment::getClassName());
			$payment = $paymentRepo->find($values->payment);
			if ($payment) {
				$originalPrice = $payment->price;
				$customPrice = $payment->getPriceByStocks($stocks, $quantities);
				$payment->setPrice($customPrice->withoutVat, FALSE);
				$this->order->payment = $payment;
				$payment->price = $originalPrice->withoutVat;
			}
		}

		return $this;
	}

	private function setOrderItem($stockId, $quantity, $add = FALSE)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		/* @var $stock Stock */
		$stock = $stockRepo->find($stockId);
		$stock->setShopVariant($this->order->shopVariant);

		if ($stock) {
			$priceLevel = ($this->order->user && $this->order->user->group) ? $this->order->user->group->level : NULL;
			$price = $stock->getPrice($priceLevel);
			$locale = $this->order->locale;
			$oldQuantity = $this->order->getItemCount($stock, FALSE);
			$newQuantity = $add ? ($quantity + $oldQuantity) : $quantity;
			$this->order->setItem($stock, $price, $newQuantity, $locale);
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

	public function render()
	{
		$defaultCurrency = $this->exchange->getDefault();
		$webCurrency = $this->order->currency;
		if ($webCurrency && $webCurrency !== $defaultCurrency->getCode()) {
			$this->exchange->setWeb($webCurrency);
			if ($this->order->rate && array_key_exists($webCurrency, $this->exchange)) {
				$currency = $this->exchange[$webCurrency];
				$rateRelated = ExchangeHelper::getRelatedRate($this->order->rate, $currency);
				$this->exchange->addRate($webCurrency, $rateRelated);
			}
		}
		$this->template->exchange = $this->exchange;
		$currency = $this->exchange[$this->exchange->getWeb()];
		$this->template->currencySymbol = $currency->getFormat()->getSymbol();
		$this->template->order = $this->order;
		parent::render();
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
