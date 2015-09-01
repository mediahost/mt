<?php

namespace App\FrontModule\Presenters;

use App\Components\Basket\Form\IPaymentsFactory;
use App\Components\Basket\Form\IPersonalFactory;
use App\Components\Basket\Form\Payments;
use App\Components\Basket\Form\Personal;
use App\Model\Entity\Order;
use Doctrine\ORM\NoResultException;

class CartPresenter extends BasePresenter
{

	/** @var IPaymentsFactory @inject */
	public $iPaymentsFactory;

	/** @var IPersonalFactory @inject */
	public $iPersonalFactory;

	public function actionDefault()
	{
		
	}

	public function actionPayments()
	{
		$this->checkEmptyCart();
	}

	public function actionAddress()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
	}

	public function actionSummary()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
		$this->checkFilledAddress();
	}

	public function handleSend()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
		$this->checkFilledAddress();

		$basket = $this->basketFacade->getBasket();
		$user = $this->user->id ? $this->user->identity : NULL;
		$order = $this->orderFacade->createFromBasket($basket, $user);

		$this->getSessionSection()->orderId = $order->id;

		$this->basketFacade->clearBasket();
		$this->redirect('done');
	}

	public function actionDone()
	{
		$orderId = $this->getSessionSection()->orderId;
		$orderRepo = $this->em->getRepository(Order::getClassName());

		try {
			if ($orderId) {
				$order = $orderRepo->find($orderId);
				if (!$order) {
					throw new NoResultException();
				}
			} else {
				throw new NoResultException();
			}
		} catch (NoResultException $e) {
			$this->flashMessage($this->translator->translate('cart.order.wasntFoundWasExecuted'), 'info');
			$this->redirect('Homepage:');
		}

		$this->getSessionSection()->orderId = NULL;

		$this->template->order = $order;
	}

	private function checkEmptyCart()
	{
		if ($this->basketFacade->getIsEmpty()) {
			$this->redirect('default');
		}
	}

	private function checkSelectedPayments()
	{
		// TODO
	}

	private function checkFilledAddress()
	{
		// TODO
	}

	private function getSessionSection()
	{
		$section = $this->getSession(get_class($this));
		if (!$section->orderId) {
			$section->orderId = NULL;
		}
		return $section;
	}

	/** @return Payments */
	public function createComponentPayments()
	{
		$control = $this->iPaymentsFactory->create();
		$control->setAjax(TRUE);
		$control->onAfterSave = function () {
			if ($this->isAjax()) {
				$this->redrawControl();
			} else {
				$this->redirect('address');
			}
		};
		return $control;
	}

	/** @return Personal */
	public function createComponentPersonal()
	{
		$control = $this->iPersonalFactory->create();
		$control->onAfterSave = function () {
			$this->redirect('summary');
		};
		return $control;
	}

}
