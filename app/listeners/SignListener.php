<?php

namespace App\Listeners;

use App\Extensions\Settings\SettingsStorage;
use App\Mail\Messages\ICreateRegistrationMessageFactory;
use App\Mail\Messages\IVerificationMessageFactory;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use App\Model\Storage\SignUpStorage;
use h4kuna\Exchange\Exchange;
use h4kuna\Exchange\UnknownCurrencyException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\Translator;
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

	/** @var SettingsStorage @inject */
	public $settingsStorage;

	/** @var Translator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;

	// </editor-fold>

	public function __construct(Application $application)
	{
		$this->application = $application->presenter;
	}

	public function getSubscribedEvents()
	{
		return array(
			'App\Components\Auth\FacebookConnect::onSuccess' => 'onStartup',
			'App\Components\Auth\SignUp::onSuccess' => 'onStartup',
			'App\Components\Auth\TwitterConnect::onSuccess' => 'onStartup',
			'App\Components\Auth\Required::onSuccess' => 'onRequiredSuccess',
			'App\Components\Auth\SignIn::onSuccess' => 'onSuccess',
			'App\Components\Auth\Recovery::onSuccess' => 'onRecovery',
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
			$message = $this->translator->translate('%value% is already registered.', NULL, ['value' => $user->mail]);
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

			$flash = $this->translator->translate('We have sent you a verification e-mail. Please check your inbox!');
			$control->presenter->flashMessage($flash, 'success');
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
		$message = $this->translator->translate('Your password has been successfully changed!');
		$presenter->flashMessage($message, 'success');
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

		$flash = $this->translator->translate('Your account has been seccessfully created.');
		$presenter->flashMessage($flash, 'success');
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
			$presenter->user->setExpiration($this->settingsStorage->expiration->remember, FALSE);
		} else {
			$presenter->user->setExpiration($this->settingsStorage->expiration->notRemember, TRUE);
		}

		try {
			$this->exchange->setWeb($user->currency);
		} catch (UnknownCurrencyException $ex) {
			$this->exchange->setWeb($this->exchange->getDefault()->getCode());
		}

		$presenter->user->login($user);
		$presenter->flashMessage($this->translator->translate('You are logged in.'), 'success');
		$presenter->restoreRequest($presenter->backlink);

		$params = [
			'locale' => $user->locale,
		];

		if ($presenter->user->isAllowed('dashboard')) {
			$presenter->redirect(self::REDIRECT_AFTER_LOGIN_DASHBOARD, $params);
		} else {
			$presenter->redirect(self::REDIRECT_AFTER_LOGIN_HOMEPAGE, $params);
		}
	}

}
