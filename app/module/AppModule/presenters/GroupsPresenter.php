<?php

namespace App\AppModule\Presenters;

use App\Components\Grids\Group\GroupsGrid;
use App\Components\Grids\Group\IGroupsGridFactory;
use App\Components\Group\GroupControl;
use App\Components\Group\IGroupControlFactory;
use App\Model\Entity\Group;
use App\TaggedString;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class GroupsPresenter extends BasePresenter
{

	/** @var Group */
	private $groupEntity;

	/** @var EntityRepository */
	private $groupRepo;

	// <editor-fold desc="injects">

	/** @var IGroupControlFactory @inject */
	public $iGroupControlFactory;

	/** @var IGroupsGridFactory @inject */
	public $iGroupsGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->groupRepo = $this->em->getRepository(Group::getClassName());
	}

	/**
	 * @secured
	 * @resource('groups')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('groups')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->groupEntity = new Group();
		$this['groupForm']->setGroup($this->groupEntity);
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('groups')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->groupEntity = $this->groupRepo->find($id);
		if (!$this->groupEntity) {
			$this->flashMessage('This group wasn\'t found.', 'error');
			$this->redirect('default');
		} else {
			$this['groupForm']->setGroup($this->groupEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->isAdd = $this->groupEntity->isNew();
	}

	/**
	 * @secured
	 * @resource('groups')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$user = $this->groupRepo->find($id);
		if (!$user) {
			$this->flashMessage('Group wasn\'t found.', 'danger');
		} else {
			try {
				$this->groupRepo->delete($user);
				$this->flashMessage('User was deleted.', 'success');
			} catch (Exception $e) {
				$this->flashMessage('This group can\'t be deleted.', 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return GroupControl */
	public function createComponentGroupForm()
	{
		$control = $this->iGroupControlFactory->create();
		$control->onAfterSave = function (Group $savedGroup) {
			$message = new TaggedString('Group \'%s\' was successfully saved.', (string) $savedGroup);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return GroupsGrid */
	public function createComponentGroupsGrid()
	{
		$control = $this->iGroupsGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
