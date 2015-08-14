<?php

namespace App\FrontModule\Presenters;

use App\Components\Basket\Form\IPaymentsFactory;
use App\Components\Basket\Form\Payments;

class CartPresenter extends BasePresenter
{

	/** @var IPaymentsFactory @inject */
	public $iPaymentsFactory;

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
	}
	
	public function checkEmptyCart()
	{
		if ($this->basketFacade->getIsEmpty()) {
			$this->redirect('default');
		}
	}

	/** @return Payments */
	public function createComponentPayments()
	{
		$control = $this->iPaymentsFactory->create();
		$control->setAjax(TRUE);
		$control->onAfterSave = function () {
			
		};
		return $control;
	}

}
