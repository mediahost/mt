<?php

namespace App\Components\Auth;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\User;
use App\Model\Facade\UserFacade;
use App\TaggedString;
use Nette\Utils\ArrayHash;

class Recovery extends BaseControl
{

	/** @var array */
	public $onFailToken = [];
	
	/** @var array */
	public $onSuccess = [];

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var User */
	private $user;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setRenderer(new MetronicFormRenderer());
		$form->setTranslator($this->translator);

		$helpText = new TaggedString('At least %d characters long.', $this->passwordService->length);
		$helpText->setTranslator($this->translator);
		$form->addPassword('newPassword', 'New password', NULL, 255)
				->setAttribute('placeholder', 'Password')
				->setRequired('Please enter your password')
				->addRule(Form::MIN_LENGTH, 'Password must be at least %d characters long.', $this->passwordService->length)
				->setOption('description', (string) $helpText);

		$form->addPassword('passwordAgain', 'Re-type Your Password', NULL, 255)
				->setAttribute('placeholder', 'Re-type Your Password')
				->addConditionOn($form['newPassword'], Form::FILLED)
				->addRule(Form::EQUAL, 'Passwords must be equal.', $form['newPassword']);

		$form->addSubmit('recovery', 'Set new password');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$this->userFacade->recoveryPassword($this->user, $values->newPassword);
		$this->em->getDao(User::getClassName())->save($this->user);
		$this->onSuccess($this->presenter, $this->user);
	}

	/**
	 * @param type $token
	 * @return void
	 */
	public function setToken($token)
	{
		if (!$this->user = $this->userFacade->findByRecoveryToken($token)) {
			$this->onFailToken($this, $token);
		}
	}

	public function renderLogin()
	{
		$this->setTemplateFile('login');
		parent::render();
	}

}

interface IRecoveryFactory
{

	/** @return Recovery */
	function create();
}
