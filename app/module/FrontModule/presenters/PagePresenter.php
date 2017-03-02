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
		$this->changePageInfo(self::PAGE_INFO_TITLE, $this->page);
		$this->changePageInfo(self::PAGE_INFO_KEYWORDS, $this->page);
		$this->changePageInfo(self::PAGE_INFO_DESCRIPTION, $this->page);
	}
	
	public function actionTerms()
	{
		$this->page = $this->pageFacade->findByType(Page::TYPE_TERMS);
		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		$this->setView('default');
	}
	
	private function getPage($id)
	{
		if ($id) {
			$pageRepo = $this->em->getRepository(Page::getClassName());
			$this->page = $pageRepo->find($id);
		}

		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
	}

}
