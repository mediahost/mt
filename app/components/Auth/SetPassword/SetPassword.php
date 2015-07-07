<?php

namespace App\Components\Auth;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity;
use App\Model\Facade\UserFacade;
use App\TaggedString;
use Exception;
use Nette\Security;
use Nette\Utils\ArrayHash;

class SetPassword extends BaseControl
{

	/** @var array */
	public $onSuccess = [];

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var Security\User */
	private $presenterUser;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setRenderer(new MetronicFormRenderer());
		$form->setTranslator($this->translator);

		if (!$this->presenterUser) {
			throw new SetPasswordException('Must use method setUser(\Nette\Security\User)');
		}
		if (!$this->presenterUser->loggedIn) {
			throw new SetPasswordException('Only for logged users');
		}

		$user = $this->presenterUser->identity;
		$form->addText('mail', 'E-mail')
				->setEmptyValue($user->mail)
				->setDisabled();

		$helpText = new TaggedString('At least %d characters long.', $this->passwordService->length);
		$helpText->setTranslator($this->translator);
		$form->addPassword('newPassword', 'New password', NULL, 255)
				->setAttribute('placeholder', 'Password')
				->setRequired('Please enter your password')
				->addRule(Form::MIN_LENGTH, 'Password must be at least %d characters long.', $this->passwordService->length)
				->setOption('description', (string) $helpText);

		$form->addPassword('passwordAgain', 'Re-type Your Password', NULL, 255)
				->setAttribute('placeholder', 'Re-type Your Password')
				->setRequired('Please re-enter your password')
				->addRule(Form::EQUAL, 'Passwords must be equal.', $form['newPassword']);

		$form->addSubmit('save', 'Save');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$user = $this->userFacade->findByMail($this->presenterUser->identity->mail);
		$user->password = $values->newPassword;

		$userRepo = $this->em->getRepository(Entity\User::getClassName());
		$savedUser = $userRepo->save($user);

		$this->onSuccess($savedUser);
	}

	public function setUser(Security\User $user)
	{
		$this->presenterUser = $user;
	}

	public function renderLogin()
	{
		$this->setTemplateFile('login');
		parent::render();
	}

}

class SetPasswordException extends Exception
{
	
}

interface ISetPasswordFactory
{

	/** @return SetPassword */
	function create();
}
