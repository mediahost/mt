<?php

namespace App\FrontModule\Presenters;

use App\Components\Buyout\Form\IRequestFactory;
use App\Components\Buyout\Form\Request;
use App\Components\Producer\Form\ContactShop;
use App\Components\Producer\Form\IContactShopFactory;
use App\Components\Producer\Form\IModelSelectorFactory;
use App\Components\Producer\Form\ModelSelector;
use App\Model\Entity\Page;
use App\Model\Entity\ProducerModel;
use Nette\Http\Session;

class BuyoutPresenter extends BasePresenter
{

	/** @var Page */
	private $page;

	/** @var Session @inject */
	public $session;

	/** @var IModelSelectorFactory @inject */
	public $iModelSelectorFactory;

	/** @var IRequestFactory @inject */
	public $iRequestFactory;

	/** @var IContactShopFactory @inject */
	public $iContactShopFactory;

	/** @var ProducerModel */
	private $model;

	public function actionDefault($id = NULL)
	{
		if ($id !== NULL) {
			$this->model = $this->em->getRepository(ProducerModel::getClassName())->find($id);
		}

		$settings = $this->settings->modules->buyout;

		if (!$settings->enabled) {
			$this->flashMessage($this->translator->translate('This module isn\'t allowed.'), 'warning');
			$this->redirect('Homepage:');
		}

		$pages = $this->em->getRepository(Page::getClassName());
		$this->page = $pages->find($settings->pageId);

		if (!$this->page) {
			$this->flashMessage($this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]), 'warning');
			$this->redirect('Homepage:');
		}

		$this->page->setCurrentLocale($this->locale);
	}

	public function renderDefault()
	{
		$this->template->page = $this->page;
		$this->template->model = $this->model;
		
		$this->changePageInfo(self::PAGE_INFO_TITLE, $this->page);
		$this->changePageInfo(self::PAGE_INFO_KEYWORDS, $this->page);
		$this->changePageInfo(self::PAGE_INFO_DESCRIPTION, $this->page);
	}

	/** @return ModelSelector */
	public function createComponentModelSelector()
	{
		$control = $this->iModelSelectorFactory->create();
		if ($this->model) {
			$control->setModel($this->model);
		}

		$control->onAfterSelect = function ($producer, $line, $model) {
			if ($this->isAjax()) {
				$this->redrawControl();
			} else {
				$this->redirect('this', ['id' => $model->id]);
			}
		};
		return $control;
	}

	/** @return Request */
	public function createComponentRequestForm()
	{
		$control = $this->iRequestFactory->create();
		$control->onSend = function () {
			$this->flashMessage($this->translator->translate('Your request has been sent.'), 'success');
			$this->redirect('this', ['id' => NULL]);
		};

		if ($this->model) {
			$control->setModel($this->model);
		}

		return $control;
	}

	/** @return ContactShop */
	public function createComponentContactBuyout()
	{
		$control = $this->iContactShopFactory->create();
		if ($this->model) {
			$control->setBuyout();
			$control->setModel($this->model);
		}
		$control->onSend = function () {
			$this->flashMessage($this->translator->translate('Your request has been sent.'), 'success');
			$this->redirect('this', ['id' => NULL]);
		};
		return $control;
	}

}
