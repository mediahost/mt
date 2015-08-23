<?php

namespace App\FrontModule\Presenters;

use App\Components\Auth\ConnectManager;
use App\Components\Auth\IConnectManagerFactory;
use App\Components\Auth\ISetPasswordFactory;
use App\Components\Auth\SetPassword;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity;
use App\Model\Facade\CantDeleteUserException;
use App\Model\Facade\NewsletterFacade;
use App\Model\Facade\UserFacade;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class MyAccountPresenter extends BasePresenter
{

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var ISetPasswordFactory @inject */
	public $iSetPasswordFactory;

	/** @var IConnectManagerFactory @inject */
	public $iConnectManagerFactory;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

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
	 * @privilege('delete')
	 */
	public function handleDelete()
	{
		try {
			$this->userFacade->deleteById($this->user->id);
			$this->user->logout();
			$message = $this->translator->translate('Your account has been deleted');
			$this->flashMessage($message, 'success');
			$this->redirect(":Front:Homepage:");
		} catch (CantDeleteUserException $ex) {
			$message = $this->translator->translate('You can\'t delete account.');
			$this->flashMessage($message, 'danger');
			$this->redirect("this");
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
		$userDao = $this->em->getDao(Entity\User::getClassName());
		$control = $this->iConnectManagerFactory->create();
		$control->setUser($userDao->find($this->user->id));
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

	protected function createComponentInfoForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator)
				->setRenderer(new MetronicHorizontalFormRenderer);

		$form->addCheckbox('newsletter', 'Newsletter')
				->setDefaultValue($this->user->identity->subscriber ? TRUE : FALSE);

		$form->addSubmit('save', 'Save');

		$form->onSuccess[] = [$this, 'infoFormSucceeded'];
		return $form;
	}

	public function infoFormSucceeded(Form $form, ArrayHash $values)
	{
		if ($values->newsletter === TRUE) {
			$this->newsletterFacade->subscribe($this->user->identity);
		} else {
			$this->newsletterFacade->unsubscribe($this->user->identity);
		}

		$this->redirect('this');
	}

	// </editor-fold>
}
