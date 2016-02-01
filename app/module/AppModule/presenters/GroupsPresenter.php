<?php

namespace App\AppModule\Presenters;

use App\Components\Group\Grid\GroupsGrid;
use App\Components\Group\Grid\IGroupsGridFactory;
use App\Components\Group\Form\GroupEdit;
use App\Components\Group\Form\IGroupEditFactory;
use App\Model\Entity\Group;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class GroupsPresenter extends BasePresenter
{

	/** @var Group */
	private $groupEntity;

	/** @var EntityRepository */
	private $groupRepo;

	// <editor-fold desc="injects">

	/** @var IGroupEditFactory @inject */
	public $iGroupEditFactory;

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
		$nextLevel = $this->groupFacade->getLastUnusedLevel();
		if (!$nextLevel) {
			$message = $this->translator->translate('You have reached the maximum of user groups.');
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
		$this->groupEntity = new Group($this->groupFacade->getLastUnusedLevel());
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
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Group')]);
			$this->flashMessage($message, 'warning');
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
		$group = $this->groupRepo->find($id);
		if (!$group) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Group')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->groupRepo->delete($group);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $this->translator->translate('Group')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Group')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	/**
	 * @secured
	 * @resource('groups')
	 * @privilege('recountPrices')
	 */
	public function handleRecountPrices()
	{
		$this->stockFacade->recountPrices();
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return GroupEdit */
	public function createComponentGroupForm()
	{
		$control = $this->iGroupEditFactory->create();
		$control->onAfterSave = function (Group $savedGroup) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Group'), 'name' => (string) $savedGroup
			]);
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
		$control->setType(Group::TYPE_DEALER);
		return $control;
	}

	/** @return GroupsGrid */
	public function createComponentBonusGrid()
	{
		$control = $this->iGroupsGridFactory->create();
		$control->setType(Group::TYPE_BONUS);
		return $control;
	}

	// </editor-fold>
}
