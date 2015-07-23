<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Page;

class PagePresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$pageRepo = $this->em->getRepository(Page::getClassName());
		$page = $pageRepo->findOneByUrl($url);
		
		if (!$page) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		$page->setCurrentLocale($this->locale);
		if ($page->slug !== $url) {
			$this->redirect('this', ['url' => $page->slug]);
		}
		
		$this->template->page = $page;
	}

}
