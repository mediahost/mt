<?php

namespace App\FrontModule\Presenters;

use App\Components\Auth\ConnectManager;
use App\Components\Auth\IConnectManagerFactory;
use App\Components\Auth\ISetPasswordFactory;
use App\Components\Auth\SetPassword;
use App\Components\User\Form\IPersonalFactory;
use App\Components\User\Form\Personal;
use App\Helpers;
use App\LocaleHelpers;
use App\Model\Entity;
use App\Model\Entity\User;
use App\Model\Facade\CantDeleteUserException;
use App\Model\Facade\UserFacade;

class MyAccountPresenter extends BasePresenter
{

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var ISetPasswordFactory @inject */
	public $iSetPasswordFactory;

	/** @var IConnectManagerFactory @inject */
	public $iConnectManagerFactory;

	/** @var IPersonalFactory @inject */
	public $iPersonalFactory;

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('personalInfo')
	 */
	public function actionPersonalInfo()
	{
		
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('accounts')
	 */
	public function actionAccounts()
	{
		
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('password')
	 */
	public function actionPassword()
	{
		
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('orders')
	 */
	public function actionOrders()
	{
		$this->template->orders = $this->orderFacade->getAllOrders($this->user->identity);
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('order')
	 */
	public function actionOrder($id)
	{
		$orderRepo = $this->em->getRepository(Entity\Order::getClassName());
		$order = $orderRepo->find($id);
		$user = $this->user->identity;
		if ($order && (($order->user && $order->user->id == $user->id) || $order->mail == $user->mail)) {
			$this->template->order = $order;
		} else {
			$this->flashMessage('This order wasn\'t found', 'danger');
			$this->redirect('orders');
		}
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('bonus')
	 */
	public function actionBonus()
	{
		$this->template->points = $this->user->identity->bonusCount;
		$this->template->group = $this->user->identity->bonusGroup;
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('delete')
	 */
	public function actionDelete()
	{
		
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('dealer')
	 */
	public function actionDealer()
	{
		$localesArr = LocaleHelpers::getLocalesFromTranslator($this->translator);
		$this->template->allLocales = Helpers::concatArray($localesArr, '|');
		$currenciesArr = array_keys($this->exchange->getArrayCopy());
		$this->template->allCurrencies = Helpers::concatArray($currenciesArr, '|');
		$exampleTopStocks = $this->stockFacade->getTops(2);
		$exampleStockQuantity = [];
		foreach ($exampleTopStocks as $exampleStock) {
			$exampleStockQuantity[$exampleStock->id] = rand(1, $exampleStock->inStore > 3 ? 2 : $exampleStock->inStore);
		}
		$this->template->exampleStocks = $exampleStockQuantity;
		$shippingsRepo = $this->em->getRepository(Entity\Shipping::getClassName());
		$this->template->shippings = $shippingsRepo->findBy(['active' => TRUE]);
		$paymentsRepo = $this->em->getRepository(Entity\Payment::getClassName());
		$this->template->payments = $paymentsRepo->findBy(['active' => TRUE]);
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('delete')
	 */
	public function handleDelete()
	{
		try {
			$this->userFacade->deleteById($this->user->id);
			$this->user->logout();
			$message = $this->translator->translate('Your account has been deleted');
			$this->flashMessage($message, 'success');
			$this->redirect(':Front:Homepage:');
		} catch (CantDeleteUserException $ex) {
			$message = $this->translator->translate('You can\'t delete account.');
			$this->flashMessage($message, 'danger');
			$this->redirect('this');
		}
	}

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('resetToken')
	 */
	public function handleResetToken()
	{
		try {
			$userRepo = $this->em->getRepository(Entity\User::getClassName());
			$this->user->identity->resetClientId();
			$userRepo->save($this->user->identity);
			$message = $this->translator->translate('Your token has been created');
			$this->flashMessage($message, 'success');
			$this->redirect('this');
		} catch (CantDeleteUserException $ex) {
			$message = $this->translator->translate('You can\'t reset token.');
			$this->flashMessage($message, 'danger');
			$this->redirect('this');
		}
	}

	// <editor-fold desc="components">

	/** @return SetPassword */
	protected function createComponentSetPassword()
	{
		$control = $this->iSetPasswordFactory->create();
		$control->setUser($this->user);
		$control->onSuccess[] = function () {
			$message = $this->translator->translate('Password has been successfuly set!');
			$this->flashMessage($message, 'success');
			$this->redirect('this');
		};
		return $control;
	}

	/** @return ConnectManager */
	protected function createComponentConnect()
	{
		$userRepo = $this->em->getRepository(Entity\User::getClassName());
		$control = $this->iConnectManagerFactory->create();
		$control->setUser($userRepo->find($this->user->id));
		$control->setAppActivateRedirect($this->link('password'));
		$control->onConnect[] = function ($type) {
			$message = $this->translator->translate('%name% was connected.', NULL, ['name' => $type]);
			$this->flashMessage($message, 'success');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onDisconnect[] = function (Entity\User $user, $type) {
			$message = $this->translator->translate('%name% was disconnected.', NULL, ['name' => $type]);
			$this->flashMessage($message, 'success');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onLastConnection[] = function () {
			$message = $this->translator->translate('Last login method is not possible deactivate.');
			$this->flashMessage($message, 'danger');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onInvalidType[] = function ($type) {
			$message = $this->translator->translate('We can\'t find \'%name%\' to disconnect.', NULL, ['name' => $type]);
			$this->flashMessage($message, 'danger');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onUsingConnection[] = function ($type) {
			$message = $this->translator->translate('Logged %name% account is using by another account.', NULL, ['name' => $type]);
			$this->flashMessage($message, 'danger');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		return $control;
	}

	/** @return Personal */
	protected function createComponentPersonalInfo()
	{
		$control = $this->iPersonalFactory->create();
		$control->onAfterSave[] = function (User $user) {
			$this->flashMessage($this->translator->translate('Your personal data was saved.'));
			$this->redirect('this');
		};
		return $control;
	}

	// </editor-fold>
}
