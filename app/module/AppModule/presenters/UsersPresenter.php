<?php

namespace App\AppModule\Presenters;

use App\Components\User\Grid\IUsersGridFactory;
use App\Components\User\Grid\UsersGrid;
use App\Components\User\Form\IUserBasicFactory;
use App\Components\User\Form\UserBasic;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Kdyby\Doctrine\EntityRepository;
use Nette\Security\User as IdentityUser;

class UsersPresenter extends BasePresenter
{

	/** @var User */
	private $userEntity;

	/** @var EntityRepository */
	private $userRepo;

	// <editor-fold desc="injects">

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var IUserBasicFactory @inject */
	public $iUserBasicFactory;

	/** @var IUsersGridFactory @inject */
	public $iUsersGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->userRepo = $this->em->getRepository(User::getClassName());
	}

	// <editor-fold desc="actions & renderers">

	/**
	 * @secured
	 * @resource('users')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$this->template->addFilter('canEdit', $this->canEdit);
		$this->template->addFilter('canDelete', $this->canDelete);
		$this->template->addFilter('canAccess', $this->canAccess);
	}

	/**
	 * @secured
	 * @resource('users')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->userEntity = new User();
		$this['userForm']->setUser($this->userEntity);
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('users')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->userEntity = $this->userRepo->find($id);
		if (!$this->userEntity) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('User')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else if (!$this->canEdit($this->user, $this->userEntity)) {
			$message = $this->translator->translate('You can\'t edit this user.');
			$this->flashMessage($message, 'danger');
			$this->redirect('default');
		} else {
			$this['userForm']->setUser($this->userEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->isAdd = $this->userEntity->isNew();
	}

	/**
	 * @secured
	 * @resource('users')
	 * @privilege('view')
	 */
	public function actionView($id)
	{
		$message = $this->translator->translate('Not implemented yet.');
		$this->flashMessage($message, 'warning');
		$this->redirect('default');
	}

	/**
	 * @secured
	 * @resource('users')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$user = $this->userRepo->find($id);
		if (!$user) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('User')]);
			$this->flashMessage($message, 'danger');
		} else if (!$this->canDelete($this->user, $user)) {
			$message = $this->translator->translate('cannotDelete', NULL, ['name' => $this->translator->translate('User')]);
			$this->flashMessage($message, 'danger');
		} else {
			$this->userFacade->delete($user);
			$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('User')]);
			$this->flashMessage($message, 'success');
		}
		$this->redirect('default');
	}

	/**
	 * @secured
	 * @resource('users')
	 * @privilege('access')
	 */
	public function actionAccess($id)
	{
		$user = $this->userRepo->find($id);
		if (!$user) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('User')]);
			$this->flashMessage($message, 'danger');
		} else if (!$this->canAccess($this->user, $user)) {
			$message = $this->translator->translate('You can\'t access to this user.');
			$this->flashMessage($message, 'danger');
		} else {
			$this->user->login($user);
			$message = $this->translator->translate('You are logged as \'%name\'.', NULL, ['name' => $user]);
			$this->flashMessage($message, 'success');
			$this->redirect(':Front:Homepage:');
		}
		$this->redirect('default');
	}

	// </editor-fold>
	// <editor-fold desc="edit/delete priviledges">

	/**
	 * Decides if identity user can edit user
	 * @param IdentityUser $identityUser
	 * @param User $user
	 * @return boolean
	 */
	public function canEdit(IdentityUser $identityUser, User $user)
	{
		if ($identityUser->id === $user->id) {
			return FALSE;
		} else {
			// pokud je nejvyšší uživatelova role v nižších rolích přihlášeného uživatele
			// tedy může editovat pouze uživatele s nižšími rolemi
			$identityLowerRoles = $this->roleFacade->findLowerRoles($identityUser->roles);
			return in_array($user->maxRole->name, $identityLowerRoles);
		}
	}

	/**
	 * Decides if identity user can delete user
	 * @param IdentityUser $identityUser
	 * @param User $user
	 * @return boolean
	 */
	public function canDelete(IdentityUser $identityUser, User $user)
	{
		$isDeletable = $this->userFacade->isDeletable($user);
		return $this->canEdit($identityUser, $user) && $isDeletable;
	}

	/**
	 * Decides if identity user can access user
	 * @param IdentityUser $identityUser
	 * @param User $user
	 * @return boolean
	 */
	public function canAccess(IdentityUser $identityUser, User $user)
	{
		return $this->canEdit($identityUser, $user);
	}

	// </editor-fold>
	// <editor-fold desc="forms">

	/** @return UserBasic */
	public function createComponentUserForm()
	{
		$control = $this->iUserBasicFactory->create();
		$control->setIdentityRoles($this->user->roles);
		$control->onAfterSave = function (User $savedUser) {
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('User'), 'name' => (string) $savedUser
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return UsersGrid */
	public function createComponentUsersGrid()
	{
		$control = $this->iUsersGridFactory->create();
		$control->setIdentity($this->user);
		return $control;
	}

	// </editor-fold>
}
