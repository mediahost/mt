<?php

namespace App\Components\User\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use App\TaggedString;
use Kdyby\Doctrine\DuplicateEntryException;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;

/**
 * Form with basic user data.
 */
class UserBasic extends BaseControl
{

	/** @var User */
	private $user;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>
	// <editor-fold desc="variables">

	/** @var array */
	private $identityRoles = [];

	/** @var array */
	private $roles;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$mail = $form->addServerValidatedText('mail', 'E-mail')
				->addRule(Form::EMAIL, 'Fill right format')
				->addRule(Form::FILLED, 'Mail must be filled');
		if ($this->user->isNew()) {
			$mail->addServerRule([$this, 'validateMail'], $this->translator->translate('%s is already registered.'));
		} else {
			$mail->setDisabled();
		}

		$password = $form->addText('password', 'Password');
		if ($this->user->isNew()) {
			$helpText = new TaggedString('At least %d characters long.', $this->passwordService->length);
			$helpText->setTranslator($this->translator);
			$password->addRule(Form::FILLED, 'Password must be filled')
					->addRule(Form::MIN_LENGTH, 'Password must be at least %d characters long.', $this->passwordService->length)
					->setOption('description', (string) $helpText);
		}

		$role = $form->addMultiSelectBoxes('roles', 'Roles', $this->getRoles())
				->setRequired('Select any role');

		$defaultRole = $this->roleFacade->findByName(Role::USER);
		if ($defaultRole && in_array($defaultRole->getId(), $this->getRoles())) {
			$role->setDefaultValue($defaultRole->getId());
		}

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function validateMail(IControl $control, $arg = NULL)
	{
		return $this->userFacade->isUnique($control->getValue());
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		try {
			$this->save();
			$this->onAfterSave($this->user);
		} catch (DuplicateEntryException $exc) {
			$message = new TaggedString('\'%s\' is already registred', $values->mail);
			$form['mail']->addError($message);
		}
	}

	private function load(ArrayHash $values)
	{
		if (isset($values->mail)) {
			$this->user->mail = $values->mail;
		}
		if ($values->password !== NULL && $values->password !== "") {
			$this->user->setPassword($values->password);
		}
		$this->user->clearRoles();
		foreach ($values->roles as $id) {
			$roleDao = $this->em->getDao(Role::getClassName());
			$item = $roleDao->find($id);
			if ($item) {
				$this->user->addRole($item);
			}
		}
		return $this;
	}

	private function save()
	{
		$userRepo = $this->em->getRepository(User::getClassName());
		$userRepo->save($this->user);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'mail' => $this->user->mail,
			'roles' => $this->user->getRolesKeys(),
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->user) {
			throw new BaseControlException('Use setUser(\App\Model\Entity\User) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setUser(User $user)
	{
		$this->user = $user;
		return $this;
	}

	public function setIdentityRoles(array $roles)
	{
		$this->identityRoles = $roles;
	}

	private function getRoles()
	{
		if ($this->roles === NULL) {
			$this->roles = $this->roleFacade->findLowerRoles($this->identityRoles);
		}
		return $this->roles;
	}

	// </editor-fold>
}

interface IUserBasicFactory
{

	/** @return UserBasic */
	function create();
}
