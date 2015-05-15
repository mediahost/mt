<?php

namespace App\Listeners;

use App\Extensions\Settings\Model\Service\ExpirationService;
use App\Mail\Messages\ICreateRegistrationMessageFactory;
use App\Mail\Messages\IVerificationMessageFactory;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use App\Model\Storage\SignUpStorage;
use App\TaggedString;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Application\Application;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Object;

class SignListener extends Object implements Subscriber
{

	const REDIRECT_AFTER_LOGIN_HOMEPAGE = ':Front:Homepage:';
	const REDIRECT_AFTER_LOGIN_DASHBOARD = ':App:Dashboard:';
	const REDIRECT_SIGNIN_PAGE = ':Front:Sign:in';
	const REDIRECT_SIGN_UP_REQUIRED = ':Front:Sign:upRequired';

	// <editor-fold desc="variables">

	/** @var SignUpStorage @inject */
	public $session;

	/** @var EntityManager @inject */
	public $em;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var ICreateRegistrationMessageFactory @inject */
	public $createRegistrationMessage;

	/** @var IVerificationMessageFactory @inject */
	public $verificationMessage;

	/** @var Application @inject */
	public $application;

	/** @var ExpirationService @inject */
	public $expirationService;

	// </editor-fold>

	public function __construct(Application $application)
	{
		$this->application = $application->presenter;
	}

	public function getSubscribedEvents()
	{
		return array(
			'App\Components\Auth\FacebookControl::onSuccess' => 'onStartup',
			'App\Components\Auth\SignUpControl::onSuccess' => 'onStartup',
			'App\Components\Auth\TwitterControl::onSuccess' => 'onStartup',
			'App\Components\Auth\RequiredControl::onSuccess' => 'onRequiredSuccess',
			'App\Components\Auth\SignInControl::onSuccess' => 'onSuccess',
			'App\Components\Auth\RecoveryControl::onSuccess' => 'onRecovery',
			'App\FrontModule\Presenters\SignPresenter::onVerify' => 'onCreate',
		);
	}

	/**
	 * Naslouchá společně všem OAuth metodám a registračnímu formuláři
	 * Pokud uživatel existuje (má ID), pak jej přihlásíme
	 * Pokud neexistuje, pak pokračuje v registraci
	 * @param Control $control
	 * @param User $user
	 * @param bool $rememberMe
	 */
	public function onStartup(Control $control, User $user, $rememberMe = FALSE)
	{
		if ($user->id) {
			$this->onSuccess($control->presenter, $user, $rememberMe);
		} else {
			$this->session->user = $user;
			$this->checkRequire($control, $user);
		}
	}

	/**
	 * Ověřuje, zda jsou vyplněny všechny nutné položky k registraci
	 * @param Control $control
	 * @param User $user
	 */
	public function checkRequire(Control $control, User $user)
	{
		if (!$user->mail) {
			$control->presenter->redirect(self::REDIRECT_SIGN_UP_REQUIRED);
		} else {
			$this->onRequiredSuccess($control, $user);
		}
	}

	/**
	 * Zde jsou již vyplněna všechna data pro registraci
	 * @param Control $control
	 * @param User $user
	 */
	public function onRequiredSuccess(Control $control, User $user)
	{
		$existedUser = $this->userFacade->findByMail($user->mail);
		// nepodporuje automatické joinování účtů (nebylo v aplikaci požadováno)
		if (!$existedUser) {
			$this->verify($control, $user);
		} else {
			$message = new TaggedString('%s is already registered.', $user->mail);
			$control->presenter->flashMessage($message);
			$control->presenter->redirect(self::REDIRECT_SIGNIN_PAGE);
		}
	}

	/**
	 * Pro vefikovanou metodu přímo vytvoří uživatele
	 * Jinak vytvoří registraci
	 * @param Control $control
	 * @param User $user
	 */
	private function verify(Control $control, User $user)
	{
		if ($this->session->isVerified()) { // verifikovaná metoda
			$userRole = $this->roleFacade->findByName(Role::USER);
			$user->addRole($userRole);
			$savedUser = $this->em->getDao(User::getClassName())->save($user);
			$this->onCreate($control->presenter, $savedUser);
		} else {
			$registration = $this->userFacade->createRegistration($user);
			$this->session->remove();

			// Send verification e-mail
			$message = $this->verificationMessage->create();
			$message->addParameter('link', $this->application->presenter->link('//:Front:Sign:verify', $registration->verificationToken));
			$message->addTo($user->mail);
			$message->send();


			$control->presenter->flashMessage('We have sent you a verification e-mail. Please check your inbox!', 'success');
			$control->presenter->redirect(self::REDIRECT_SIGNIN_PAGE);
		}
	}

	/**
	 * After recovery password
	 * @param Presenter $presenter
	 * @param User $user
	 */
	public function onRecovery(Presenter $presenter, User $user)
	{
		$presenter->flashMessage('Your password has been successfully changed!', 'success');
		$this->onSuccess($presenter, $user);
	}

	/**
	 * After create account
	 * @param Presenter $presenter
	 * @param User $user
	 */
	public function onCreate(Presenter $presenter, User $user)
	{
		$message = $this->createRegistrationMessage->create();
		$message->addTo($user->mail);
		$message->send();

		$presenter->flashMessage('Your account has been seccessfully created.', 'success');
		$this->onSuccess($presenter, $user);
	}

	/**
	 * Only login and redirect to app
	 * @param Presenter $presenter
	 * @param User $user
	 * @param bool $rememberMe
	 */
	public function onSuccess(Presenter $presenter, User $user, $rememberMe = FALSE)
	{
		$this->session->remove();

		if ($rememberMe) {
			$presenter->user->setExpiration($this->expirationService->remember, FALSE);
		} else {
			$presenter->user->setExpiration($this->expirationService->notRemember, TRUE);
		}

		$presenter->user->login($user);
		$presenter->flashMessage('You are logged in.', 'success');

		$presenter->restoreRequest($presenter->backlink);
		if ($presenter->user->isAllowed('dashboard')) {
			$presenter->redirect(self::REDIRECT_AFTER_LOGIN_DASHBOARD);
		} else {
			$presenter->redirect(self::REDIRECT_AFTER_LOGIN_HOMEPAGE);
		}
	}

}
