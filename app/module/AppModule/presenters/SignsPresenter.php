<?php

namespace App\AppModule\Presenters;

use App\Components\Sign\Form\ISignEditFactory;
use App\Components\Sign\Form\SignEdit;
use App\Components\Sign\Grid\ISignsGridFactory;
use App\Components\Sign\Grid\SignsGrid;
use App\Model\Entity\Sign;
use App\TaggedString;
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
			$this->flashMessage('This sign wasn\'t found.', 'warning');
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
			$this->flashMessage('Sign wasn\'t found.', 'danger');
		} else {
			try {
				$this->signRepo->delete($group);
				$this->flashMessage('Sign was deleted.', 'success');
			} catch (Exception $e) {
				$this->flashMessage('This sign can\'t be deleted.', 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return SignEdit */
	public function createComponentSignForm()
	{
		$control = $this->iSignEditFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = function (Sign $savedSign) {
			$message = new TaggedString('Sign \'%s\' was successfully saved.', (string) $savedSign);
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
		$control->setLang($this->lang);
		return $control;
	}

	// </editor-fold>
}
