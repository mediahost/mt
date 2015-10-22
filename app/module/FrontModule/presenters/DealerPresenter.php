<?php

namespace App\FrontModule\Presenters;

use App\Components\Auth\ISignUpFactory;
use App\Components\Auth\SignUp;
use App\Components\User\Form\IPersonalFactory;
use App\Components\User\Form\Personal;
use App\Model\Entity\Page;
use App\Model\Entity\User;

class DealerPresenter extends BasePresenter
{

	/** @var Page */
	private $page;

	// <editor-fold desc="injects">

	/** @var IPersonalFactory @inject */
	public $iPersonalFactory;

	/** @var ISignUpFactory @inject */
	public $iSignUpFactory;

	// </editor-fold>

	public function actionDefault()
	{
		$dealer = $this->settings->modules->dealer;
		if (!$dealer->enabled) {
			$message = $this->translator->translate('This module isn\'t allowed.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->page = $pageRepo->find($dealer->pageId);

		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$this->page->setCurrentLocale($this->locale);
	}

	public function renderDefault()
	{
		$this->template->page = $this->page;
		$this->template->identity = $this->user->identity;
	}

	// <editor-fold desc="forms">

	/** @return Personal */
	public function createComponentDealerRequest()
	{
		$control = $this->iPersonalFactory->create();
		$control->setDealerRequired();
		$control->onAfterSave[] = function (User $user) {
			$this->flashMessage($this->translator->translate('Your dealer request was send'));
			$this->redirect('this');
		};
		return $control;
	}

	/** @return SignUp */
	public function createComponentDealerRegistration()
	{
		$control = $this->iSignUpFactory->create();
		$control->setCompleteInfo();
		return $control;
	}

	// </editor-fold>
}
