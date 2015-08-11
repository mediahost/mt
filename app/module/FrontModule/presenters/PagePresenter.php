<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Page;

class PagePresenter extends BasePresenter
{

	public function actionDefault($id)
	{
		$pageRepo = $this->em->getRepository(Page::getClassName());
		$page = $pageRepo->find($id);
		
		if (!$page) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		
		$page->setCurrentLocale($this->locale);
		
		$this->template->page = $page;
	}

}
