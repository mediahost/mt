<?php

namespace App\AppModule\Presenters;

use App\Components\Page\Form\IPageEditFactory;
use App\Components\Page\Form\PageEdit;
use App\Components\Page\Grid\IPagesGridFactory;
use App\Components\Page\Grid\PagesGrid;
use App\Model\Entity\Page;
use App\Model\Repository\PageRepository;
use Exception;

class PagesPresenter extends BasePresenter
{

	/** @var Page */
	private $pageEntity;

	/** @var PageRepository */
	private $pageRepo;

	// <editor-fold desc="injects">

	/** @var IPageEditFactory @inject */
	public $iPageEditFactory;

	/** @var IPagesGridFactory @inject */
	public $iPagesGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->pageRepo = $this->em->getRepository(Page::getClassName());
	}

	/**
	 * @secured
	 * @resource('pages')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('pages')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->pageEntity = new Page();
		$this['pageForm']->setPage($this->pageEntity);
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('pages')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->pageEntity = $this->pageRepo->find($id);
		if (!$this->pageEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['pageForm']->setPage($this->pageEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->isAdd = $this->pageEntity->isNew();
	}

	/**
	 * @secured
	 * @resource('pages')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$page = $this->pageRepo->find($id);
		if (!$page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->pageRepo->delete($page);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $this->translator->translate('Page')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Page')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	public function canDelete(Page $page)
	{
		return $this->user->isAllowed('pages', 'delete');
	}

	// <editor-fold desc="forms">

	/** @return PageEdit */
	public function createComponentPageForm()
	{
		$control = $this->iPageEditFactory->create();
		$control->onAfterSave = function (Page $savedPage) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Page'), 'name' => (string) $savedPage
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return PagesGrid */
	public function createComponentPagesGrid()
	{
		$control = $this->iPagesGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
