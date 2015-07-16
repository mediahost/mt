<?php

namespace App\FrontModule\Presenters;

use App\Components\Auth;
use App\Mail\Messages\IForgottenMessageFactory;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade;
use App\Model\Storage;

class SignPresenter extends BasePresenter
{

	const REDIRECT_AFTER_LOG = ':Front:Homepage:';
	const REDIRECT_NOT_LOGGED = ':Front:Sign:in';
	const REDIRECT_IS_LOGGED = ':Front:Homepage:';

	// <editor-fold desc="events">

	/** @var array */
	public $onVerify = [];

	// </editor-fold>
	// <editor-fold desc="Injects">

	/** @var Auth\IFacebookConnectFactory @inject */
	public $iFacebookConnectFactory;

	/** @var Auth\IForgottenFactory @inject */
	public $iForgottenFactory;

	/** @var Auth\IRecoveryFactory @inject */
	public $iRecoveryFactory;

	/** @var Auth\IRequiredFactory @inject */
	public $iRequiredFactory;

	/** @var Auth\ISignInFactory @inject */
	public $iSignInFactory;

	/** @var Auth\ISignUpFactory @inject */
	public $iSignUpFactory;

	/** @var Auth\ITwitterConnectFactory @inject */
	public $iTwitterConnectFactory;

	/** @var Storage\SignUpStorage @inject */
	public $session;

	/** @var Facade\UserFacade @inject */
	public $userFacade;

	/** @var Facade\RoleFacade @inject */
	public $roleFacade;

	/** @var IForgottenMessageFactory @inject */
	public $forgottenMessage;

	// </editor-fold>

	protected function startup()
	{
		$this->isLoggedIn();
		parent::startup();
	}

	// <editor-fold desc="Actions & renders">

	/** @param string $role */
	public function actionIn()
	{
		$this->session->wipe();
		$this->session->role = Role::USER;
	}

	/**
	 * @param string $role
	 */
	public function actionUp()
	{
		$this->session->wipe();
		$this->session->role = Role::USER;
	}

	public function renderUpRequired()
	{
		$this->template->role = $this->session->role;
	}

	/** @param string $token */
	public function actionVerify($token)
	{
		$registration = $this->userFacade->findByVerificationToken($token);
		if ($registration) {
			$userRole = $this->roleFacade->findByName(Role::USER);
			$user = $this->userFacade->createFromRegistration($registration, $userRole);
			$message = $this->translator->translate('Your e-mail has been seccessfully verified!');
			$this->flashMessage($message, 'success');
			$this->onVerify($this, $user);
		} else {
			$message = $this->translator->translate('Verification token is incorrect.');
			$this->flashMessage($message, 'warning');
			$this->redirect('in');
		}
	}

	/** @param string $token */
	public function actionRecovery($token)
	{
		$this['recovery']->setToken($token);
	}

	// </editor-fold>

	/**
	 * Redirect logged to certain destination.
	 * @param type $redirect
	 * @return bool
	 */
	private function isLoggedIn($redirect = TRUE)
	{
		$isLogged = $this->user->isLoggedIn();
		if ($isLogged && $redirect) {
			$this->redirect(self::REDIRECT_IS_LOGGED);
		}
		return $isLogged;
	}

	// <editor-fold desc="controls">

	/** @return Auth\Forgotten */
	protected function createComponentForgotten()
	{
		$control = $this->iForgottenFactory->create();
		$control->onSuccess[] = function (User $user) {

			// Send e-mail with recovery link
			$message = $this->forgottenMessage->create();
			$message->addParameter('link', $this->link('//:Front:Sign:recovery', $user->recoveryToken));
			$message->addTo($user->mail);
			$message->send();

			$flash = $this->translator->translate('Recovery link has been sent to your mail.');
			$this->flashMessage($flash);
			$this->redirect(':Front:Sign:in');
		};
		$control->onMissingUser[] = function ($mail) {
			$message = $this->translator->translate('We do not register any user with mail \'%mail%\'.', NULL, ['mail' => $mail]);
			$this->flashMessage($message, 'warning');
			$this->redirect(':Front:Sign:lostPassword');
		};
		return $control;
	}

	/** @return Auth\Recovery */
	protected function createComponentRecovery()
	{
		$control = $this->iRecoveryFactory->create();
		$control->onFailToken[] = function () {
			$message = $this->translator->translate('Token to recovery your password is no longer active. Please request new one.');
			$this->flashMessage($message, 'info');
			$this->redirect(':Front:Sign:lostPassword');
		};
		return $control;
	}

	/** @return Auth\Required */
	protected function createComponentRequired()
	{
		return $this->iRequiredFactory->create();
	}

	/** @return Auth\SignIn */
	protected function createComponentSignIn()
	{
		return $this->iSignInFactory->create();
	}

	/** @return Auth\SignUp */
	protected function createComponentSignUp()
	{
		return $this->iSignUpFactory->create();
	}

	// </editor-fold>
}
