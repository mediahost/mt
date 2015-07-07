<?php

namespace App\Components\Auth;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\User;
use App\Model\Facade\UserFacade;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Utils\ArrayHash;

class SignIn extends BaseControl
{
	// <editor-fold desc="events">

	/** @var array */
	public $onSuccess = [];

	// </editor-fold>
	// <editor-fold desc="injects">

	/** @var IFacebookConnectFactory @inject */
	public $iFacebookConnectFactory;

	/** @var ITwitterConnectFactory @inject */
	public $iTwitterConnectFactory;

	/** @var UserFacade @inject */
	public $userFacade;

	// </editor-fold>

	/** @var User */
	private $user;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setRenderer(new MetronicFormRenderer());
		$form->setTranslator($this->translator);

		$mail = $form->addText('mail', 'E-mail')
				->setAttribute('placeholder', 'E-mail');
		if (!$this->user) {
			$mail->setRequired('Please enter your e-mail');
		}

		$form->addPassword('password', 'Password')
				->setAttribute('placeholder', 'Password')
				->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Remember')
						->getLabelPrototype()->class = "rememberme check";

		$form->addSubmit('signIn', 'Sign in');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceeded(Form $form, ArrayHash $values)
	{
		if ($this->user) {
			$user = $this->user;
		} else {
			$user = $this->userFacade->findByMail($values->mail);
		}
		try {
			if (!$user) {
				throw new AuthenticationException('Username is incorrect.', IAuthenticator::IDENTITY_NOT_FOUND);
			} elseif (!$user->verifyPassword($values->password)) {
				throw new AuthenticationException('Password is incorrect.', IAuthenticator::INVALID_CREDENTIAL);
			} elseif ($user->needsRehash()) {
				$this->em->persist($user);
			}

			// Remove recovery data if exists
			if ($user->recoveryToken !== NULL) {
				$user->removeRecovery();
				$this->em->persist($user);
			}
			$this->em->flush();

			$this->onSuccess($this->presenter, $user, $values->remember);
		} catch (AuthenticationException $e) {
			$form->addError('Incorrect login or password!');
		}
	}

	/**
	 * V případě přidání uživatele není potřeba zadávat e-mail pro přihlášení
	 * @param User $user
	 */
	public function setUserToSign(User $user)
	{
		$this->user = $user;
	}

	public function renderLogin()
	{
		$this->setTemplateFile('login');
		parent::render();
	}

	public function renderSocial()
	{
		$this->setTemplateFile('social');
		parent::render();
	}

	public function renderLock()
	{
		$this->setTemplateFile('lock');
		$this->template->loggedUser = $this->user;
		parent::render();
	}

	// <editor-fold desc="controls">

	/** @return FacebookConnect */
	protected function createComponentFacebook()
	{
		return $this->iFacebookConnectFactory->create();
	}

	/** @return TwitterConnect */
	protected function createComponentTwitter()
	{
		return $this->iTwitterConnectFactory->create();
	}

	// </editor-fold>
}

interface ISignInFactory
{

	/** @return SignIn */
	function create();
}
