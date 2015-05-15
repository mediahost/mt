<?php

namespace App\FrontModule\Presenters;

use App\Components\Auth;
use App\Mail\Messages\IForgottenMessageFactory;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade;
use App\Model\Storage;
use App\TaggedString;

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

	/** @var Auth\IFacebookControlFactory @inject */
	public $iFacebookControlFactory;

	/** @var Auth\IForgottenControlFactory @inject */
	public $iForgottenControlFactory;

	/** @var Auth\IRecoveryControlFactory @inject */
	public $iRecoveryControlFactory;

	/** @var Auth\IRequiredControlFactory @inject */
	public $iRequiredControlFactory;

	/** @var Auth\ISignInControlFactory @inject */
	public $iSignInControlFactory;

	/** @var Auth\ISignUpControlFactory @inject */
	public $iSignUpControlFactory;

	/** @var Auth\ITwitterControlFactory @inject */
	public $iTwitterControlFactory;

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
			$this->flashMessage('Your e-mail has been seccessfully verified!', 'success');
			$this->onVerify($this, $user);
		} else {
			$this->flashMessage('Verification token is incorrect.', 'warning');
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

	/** @return Auth\ForgottenControl */
	protected function createComponentForgotten()
	{
		$control = $this->iForgottenControlFactory->create();
		$control->onSuccess[] = function (User $user) {

			// Send e-mail with recovery link
			$message = $this->forgottenMessage->create();
			$message->addParameter('link', $this->link('//:Front:Sign:recovery', $user->recoveryToken));
			$message->addTo($user->mail);
			$message->send();

			$this->flashMessage('Recovery link has been sent to your mail.');
			$this->redirect(':Front:Sign:in');
		};
		$control->onMissingUser[] = function ($mail) {
			$message = new TaggedString('We do not register any user with mail \'%s\'.', $mail);
			$this->flashMessage($message, 'warning');
			$this->redirect(':Front:Sign:lostPassword');
		};
		return $control;
	}

	/** @return Auth\RecoveryControl */
	protected function createComponentRecovery()
	{
		$control = $this->iRecoveryControlFactory->create();
		$control->onFailToken[] = function () {
			$this->flashMessage('Token to recovery your password is no longer active. Please request new one.', 'info');
			$this->redirect(':Front:Sign:lostPassword');
		};
		return $control;
	}

	/** @return Auth\RequiredControl */
	protected function createComponentRequired()
	{
		return $this->iRequiredControlFactory->create();
	}

	/** @return Auth\SignInControl */
	protected function createComponentSignIn()
	{
		return $this->iSignInControlFactory->create();
	}

	/** @return Auth\SignUpControl */
	protected function createComponentSignUp()
	{
		return $this->iSignUpControlFactory->create();
	}

	// </editor-fold>
}
