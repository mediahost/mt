<?php

namespace App\AppModule\Presenters;

use App\Components\Sign\Form\ISignEditFactory;
use App\Components\Sign\Form\SignEdit;
use App\Components\Sign\Grid\ISignsGridFactory;
use App\Components\Sign\Grid\SignsGrid;
use App\Model\Entity\Sign;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class SignsPresenter extends BasePresenter
{

	/** @var Sign */
	private $signEntity;

	/** @var EntityRepository */
	private $signRepo;

	// <editor-fold desc="injects">

	/** @var ISignEditFactory @inject */
	public $iSignEditFactory;

	/** @var ISignsGridFactory @inject */
	public $iSignsGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->signRepo = $this->em->getRepository(Sign::getClassName());
	}

	/**
	 * @secured
	 * @resource('signs')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('signs')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->signEntity = new Sign();
		$this['signForm']->setSign($this->signEntity);
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('signs')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->signEntity = $this->signRepo->find($id);
		if (!$this->signEntity) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Sign')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['signForm']->setSign($this->signEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->isAdd = $this->signEntity->isNew();
	}

	/**
	 * @secured
	 * @resource('signs')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$group = $this->signRepo->find($id);
		if (!$group) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Sign')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->signRepo->delete($group);
				$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('Sign')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDelete', NULL, ['name' => $this->translator->translate('Sign')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return SignEdit */
	public function createComponentSignForm()
	{
		$control = $this->iSignEditFactory->create();
		$control->onAfterSave = function (Sign $savedSign) {
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('Sign'), 'name' => (string) $savedSign
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return SignsGrid */
	public function createComponentSignsGrid()
	{
		$control = $this->iSignsGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
