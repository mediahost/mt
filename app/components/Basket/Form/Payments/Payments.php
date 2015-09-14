<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\PaymentsFacade;

class Payments extends BaseControl
{

	/** @var BasketFacade @inject */
	public $basketFacade;

	/** @var PaymentsFacade @inject */
	public $paymentFacade;

	/** @var int */
	private $priceLevel = NULL;

	// <editor-fold desc="events">

	/** @var array */
	public $onSend = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay' . ($this->isSendOnChange ? ' sendOnChange' : ''));
		}

		$basket = $this->basketFacade->getBasket();
		$shippings = $this->paymentFacade->getShippingsList($basket, $this->priceLevel);
		$payments = $this->paymentFacade->getPaymentsList($basket, $this->priceLevel);

		$form->addRadioList('shipping', 'cart.headline.selectShipping', $shippings);
		$form->addRadioList('payment', 'cart.headline.selectPayment', $payments);
		$this->allowPayments($form);

		$form->addSubmit('send', 'Send');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$paymentRepo = $this->em->getRepository(Payment::getClassName());

		if ($values->shipping) {
			$shipping = $shippingRepo->find($values->shipping);
			if ($shipping) {
				$this->basketFacade->setShipping($shipping, TRUE);
			}
		}
		$allowedPayments = $this->allowPayments($form);

		if ($values->payment && in_array($values->payment, array_keys($allowedPayments))) {
			$payment = $paymentRepo->find($values->payment);
			if ($payment) {
				$this->basketFacade->setPayment($payment);
			}
		}

		if (!$this->basketFacade->hasPayments() && $form['send']->submittedBy) {
			$form->addError($this->translator->translate('cart.form.validator.shippingAndPaymentRequired'));
		}


		if ($form['send']->submittedBy) {
			if (!$this->basketFacade->hasPayments()) {
				$form->addError($this->translator->translate('cart.form.validator.shippingAndPaymentRequired'));
			}
			if (!$form->hasErrors()) {
				$this->onSend();
			}
		}

		if ($this->presenter->isAjax()) {
			$this->redrawControl();
		}
	}

	private function allowPayments(Form $form)
	{
		$basket = $this->basketFacade->getBasket();
		if ($basket->shipping) {
			$paymentRepo = $this->em->getRepository(Payment::getClassName());
			$allowedPayments = $paymentRepo->findPairs(['shippings.id' => $basket->shipping->id], 'name');
		} else {
			$allowedPayments = [];
		}

		$allPayments = $this->paymentFacade->getPaymentsList($basket);
		$denyedPayments = array_diff_key($allPayments, $allowedPayments);

		$form['payment']->setDisabled(array_keys($denyedPayments));

		return $allowedPayments;
	}

	/** @return array */
	protected function getDefaults()
	{
		$basket = $this->basketFacade->getBasket();
		$values = [
			'shipping' => $basket->shipping && $basket->shipping->active ? $basket->shipping->id : NULL,
			'payment' => $basket->payment && $basket->payment->active ? $basket->payment->id : NULL,
		];
		return $values;
	}

	protected function getPaymentsPrice()
	{
		$basket = $this->basketFacade->getBasket();
		return $basket->getPaymentsPrice(NULL, $this->priceLevel);
	}

	public function render()
	{
		$this->template->price = $this->getPaymentsPrice();
		parent::render();
	}

	// <editor-fold desc="setters & getters">

	public function setPriceLevel($level)
	{
		$this->priceLevel = $level;
		return $this;
	}

	// </editor-fold>

}

interface IPaymentsFactory
{

	/** @return Payments */
	function create();
}
