<?php

namespace App\FrontModule\Presenters;

use App\Components\Basket\Form\IPaymentsFactory;
use App\Components\Basket\Form\IPersonalFactory;
use App\Components\Basket\Form\Payments;
use App\Components\Basket\Form\Personal;

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
		// TODO
		// uloží ID objednávky do session
		
		$this->basketFacade->clearBasket();
		$this->redirect('send');
	}

	public function actionSend()
	{
		// TODO
		// odstraní ID objednávky ze session
		// načte objednávku podle ID
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
