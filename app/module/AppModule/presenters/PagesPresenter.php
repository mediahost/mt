<?php

namespace App\AppModule\Presenters;

use App\Components\Page\Form\IPageEditFactory;
use App\Components\Page\Form\PageEdit;
use App\Components\Page\Grid\IPagesGridFactory;
use App\Components\Page\Grid\PagesGrid;
use App\Model\Entity\Page;
use App\Model\Repository\PageRepository;
use App\TaggedString;
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
			$this->flashMessage('This page wasn\'t found.', 'warning');
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
			$this->flashMessage('Page wasn\'t found.', 'danger');
		} else {
			try {
				$this->pageRepo->delete($page);
				$this->flashMessage('Page was deleted.', 'success');
			} catch (Exception $e) {
				$this->flashMessage('This page can\'t be deleted.', 'danger');
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
		$control->setLang($this->lang);
		$control->onAfterSave = function (Page $savedPage) {
			$message = new TaggedString('Page \'%s\' was successfully saved.', (string) $savedPage);
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
		$control->setLang($this->lang);
		return $control;
	}

	// </editor-fold>
}
