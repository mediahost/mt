<?php

namespace App\AppModule\Presenters;

use App\Components\User\Grid\IUsersGridFactory;
use App\Components\User\Grid\UsersGrid;
use App\Components\User\Form\IUserBasicFactory;
use App\Components\User\Form\UserBasic;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use App\TaggedString;
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
			$this->flashMessage('This user wasn\'t found.', 'error');
			$this->redirect('default');
		} else if (!$this->canEdit($this->user, $this->userEntity)) {
			$this->flashMessage('You can\'t edit this user.', 'danger');
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
		$this->flashMessage('Not implemented yet.', 'warning');
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
			$this->flashMessage('User wasn\'t found.', 'danger');
		} else if (!$this->canDelete($this->user, $user)) {
			$this->flashMessage('You can\'t delete this user.', 'danger');
		} else {
			$this->userFacade->delete($user);
			$this->flashMessage('User was deleted.', 'success');
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
			$this->flashMessage('User wasn\'t found.', 'danger');
		} else if (!$this->canAccess($this->user, $user)) {
			$this->flashMessage('You can\'t access to this user.', 'danger');
		} else {
			$this->user->login($user);
			$message = new TaggedString('You are logged as \'%s\'.', $user);
			$this->flashMessage($message, 'success');
			$this->redirect('Dashboard:');
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
			$message = new TaggedString('User \'%s\' was successfully saved.', (string) $savedUser);
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
