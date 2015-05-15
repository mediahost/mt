<?php

namespace App\FrontModule\Presenters;

use App\Components\Auth;
use App\Model\Entity\User;
use Nette\Security\IUserStorage;

/**
 * Lock Screen presenter.
 */
class LockScreenPresenter extends BasePresenter
{

	/** @var User */
	private $loggedUser;

	// <editor-fold desc="Injects">

	/** @var Auth\ISignInControlFactory @inject */
	public $iSignInControlFactory;

	/** @var Auth\IFacebookControlFactory @inject */
	public $iFacebookControlFactory;

	/** @var Auth\ITwitterControlFactory @inject */
	public $iTwitterControlFactory;

	// </editor-fold>

	public function actionDefault()
	{
		if (!$this->user->loggedIn && $this->user->identity && $this->user->logoutReason === IUserStorage::INACTIVITY) {
			$userDao = $this->em->getDao(User::getClassName());
			$this->loggedUser = $userDao->find($this->user->identity->id);
		}
		if (!$this->loggedUser) {
			$this->redirect(":Front:Sign:in");
		}
	}

	public function renderDefault()
	{
		$this->template->loggedUser = $this->loggedUser;
	}

	protected function beforeRender()
	{
		$this->setDemoLayout();
		parent::beforeRender();
	}

	// <editor-fold desc="controls">

	/** @return Auth\SignInControl */
	protected function createComponentSignIn()
	{
		$control = $this->iSignInControlFactory->create();
		$control->setUserToSign($this->loggedUser);
		return $control;
	}

	/** @return Auth\FacebookControl */
	protected function createComponentFacebook()
	{
		return $this->iFacebookControlFactory->create();
	}

	/** @return Auth\TwitterControl */
	protected function createComponentTwitter()
	{
		return $this->iTwitterControlFactory->create();
	}

	// </editor-fold>
}
