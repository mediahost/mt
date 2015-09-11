<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Page;

class PagePresenter extends BasePresenter
{
	
	/** @var Page */
	private $page;

	public function actionDefault($id)
	{
		$this->getPage($id);
	}

	public function renderDefault()
	{
		$this->page->setCurrentLocale($this->locale);
		$this->template->page = $this->page;
	}
	
	public function actionTerms()
	{
		$id = $this->settings->pageInfo->termPageId;
		$this->getPage($id);
		$this->setView('default');
	}
	
	private function getPage($id)
	{
		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->page = $pageRepo->find($id);
		
		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
	}

}
