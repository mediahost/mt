<?php

namespace App\AppModule\Presenters;

use App\Components\Auth\ConnectManagerControl;
use App\Components\Auth\IConnectManagerControlFactory;
use App\Components\Auth\ISetPasswordControlFactory;
use App\Components\Auth\SetPasswordControl;
use App\Model\Entity;
use App\Model\Facade\CantDeleteUserException;
use App\Model\Facade\UserFacade;
use App\TaggedString;

class ProfilePresenter extends BasePresenter
{

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var ISetPasswordControlFactory @inject */
	public $iSetPasswordControlFactory;

	/** @var IConnectManagerControlFactory @inject */
	public $iConnectManagerControlFactory;

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('default')
	 */
	public function actionDefault()
	{

	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('settings')
	 */
	public function actionSettings()
	{
		$this->redirect('connectManager');
	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('settings')
	 */
	public function actionConnectManager()
	{

	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('settings')
	 */
	public function actionSetPassword()
	{

	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('delete')
	 */
	public function actionDelete()
	{

	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('delete')
	 */
	public function handleDelete()
	{
		try {
			$this->userFacade->deleteById($this->user->id);
			$this->user->logout();
			$this->flashMessage('Your account has been deleted', 'success');
			$this->redirect(":Front:Homepage:");
		} catch (CantDeleteUserException $ex) {
			$this->flashMessage('You can\'t delete account, because you are only one admin for your company.', 'danger');
			$this->redirect("this");
		}
	}

	// <editor-fold desc="components">

	/** @return SetPasswordControl */
	protected function createComponentSetPassword()
	{
		$control = $this->iSetPasswordControlFactory->create();
		$control->setUser($this->user);
		$control->onSuccess[] = function () {
			$this->flashMessage('Password has been successfuly set!', 'success');
			$this->redirect('this');
		};
		return $control;
	}

	/** @return ConnectManagerControl */
	protected function createComponentConnect()
	{
		$userDao = $this->em->getDao(Entity\User::getClassName());
		$control = $this->iConnectManagerControlFactory->create();
		$control->setUser($userDao->find($this->user->id));
		$control->setAppActivateRedirect($this->link('setPassword'));
		$control->onConnect[] = function ($type) {
			$message = new TaggedString('%s was connected.', $type);
			$this->flashMessage($message, 'success');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onDisconnect[] = function (Entity\User $user, $type) {
			$message = new TaggedString('%s was disconnected.', $type);
			$this->flashMessage($message, 'success');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onLastConnection[] = function () {
			$this->flashMessage('Last login method is not possible deactivate.', 'danger');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onInvalidType[] = function ($type) {
			$message = new TaggedString('We can\'t find \'%s\' to disconnect.', $type);
			$this->flashMessage($message, 'danger');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		$control->onUsingConnection[] = function ($type) {
			$message = new TaggedString('Logged %s account is using by another account.', $type);
			$this->flashMessage($message, 'danger');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};
		return $control;
	}

	// </editor-fold>
}
