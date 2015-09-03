<?php

namespace App\AppModule\Presenters;

use App\Components\Payments\Form\IPaymentEditFactory;
use App\Components\Payments\Form\IShippingEditFactory;
use App\Components\Payments\Form\PaymentEdit;
use App\Components\Payments\Form\ShippingEdit;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Repository\PaymentRepository;
use App\Model\Repository\ShippingRepository;

class ShippingsPresenter extends BasePresenter
{

	/** @var ShippingRepository */
	private $shippingRepo;

	/** @var PaymentRepository */
	private $paymentRepo;

	// <editor-fold desc="injects">

	/** @var IShippingEditFactory @inject */
	public $iShippingFactory;

	/** @var IPaymentEditFactory @inject */
	public $iPaymentFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$this->paymentRepo = $this->em->getRepository(Payment::getClassName());
	}

	/**
	 * @secured
	 * @resource('shippings')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$this->template->payments = $this->paymentRepo->findAll();
		$this->template->shippings = $this->shippingRepo->findAll();
	}

	/**
	 * @secured
	 * @resource('shippings')
	 * @privilege('editShipping')
	 */
	public function actionEditShipping($id)
	{
		$shipping = $this->shippingRepo->find($id);
		if (!$shipping) {
			$message = $this->translator->translate('wasntFoundIt', NULL, ['name' => $this->translator->translate('Shipping')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['shippingForm']->setShipping($shipping);
			$this->template->shippingName = $this->translator->translate($shipping);
		}
	}

	/**
	 * @secured
	 * @resource('shippings')
	 * @privilege('editPayment')
	 */
	public function actionEditPayment($id)
	{
		$payment = $this->paymentRepo->find($id);
		if (!$payment) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Payment')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['paymentForm']->setPayment($payment);
			$this->template->paymentName = $this->translator->translate($payment);
		}
	}

	// <editor-fold desc="forms">

	/** @return ShippingEdit */
	public function createComponentShippingForm()
	{
		$control = $this->iShippingFactory->create();
		$control->onAfterSave = function ($shipping) {
			$message = $this->translator->translate('successfullySavedIt', NULL, [
				'type' => $this->translator->translate('Shipping'), 'name' => $this->translator->translate($shipping)
			]);
			$this->presenter->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	/** @return PaymentEdit */
	public function createComponentPaymentForm()
	{
		$control = $this->iPaymentFactory->create();
		$control->onAfterSave = function ($payment) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Payment'), 'name' => $this->translator->translate($payment)
			]);
			$this->presenter->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
}
