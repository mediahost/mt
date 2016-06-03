<?php

namespace App\FrontModule\Presenters;

use App\Components\Producer\Form\ContactShop;
use App\Components\Producer\Form\IContactShopFactory;
use App\Components\Producer\Form\IModelSelectorFactory;
use App\Components\Producer\Form\ModelSelector;
use App\Model\Entity\Page;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerModel;

class ServicePresenter extends BasePresenter
{

	/** @var Page */
	private $page;

	/** @var ProducerModel */
	private $model;

	// <editor-fold desc="injects">

	/** @var IModelSelectorFactory @inject */
	public $iModelSelectorFactory;

	/** @var IContactShopFactory @inject */
	public $iContactShopFactory;

	// </editor-fold>

	public function actionDefault($id = NULL)
	{
		$service = $this->settings->modules->service;
		if (!$service->enabled) {
			$message = $this->translator->translate('This module isn\'t allowed.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->page = $pageRepo->find($service->pageId);

		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		if ($id) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$this->model = $modelRepo->find($id);
			if (!$this->model) {
				$message = $this->translator->translate('wasntFoundHe', NULL, ['name' => $this->translator->translate('Model')]);
				$this->flashMessage($message, 'warning');
				$this->redirect('this', ['id' => NULL]);
			}
		}

		$this->page->setCurrentLocale($this->locale);
	}

	public function renderDefault($id = NULL)
	{
		if ($id) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$this->model = $modelRepo->find($id);
		}
		if ($this->model) {
			$this->model->setCurrentLocale($this->locale);
		}

		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$producers = $producerRepo->findAll();

		$this->template->page = $this->page;
		$this->template->producersTree = $this['modelSelector']->getProducersTree();
		$this->template->producers = $producers;
		$this->template->model = $this->model;

		$this->changePageInfo(self::PAGE_INFO_TITLE, $this->page);
		$this->changePageInfo(self::PAGE_INFO_KEYWORDS, $this->page);
		$this->changePageInfo(self::PAGE_INFO_DESCRIPTION, $this->page);

		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

	// <editor-fold desc="forms">

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

	/** @return ContactShop */
	public function createComponentContactService()
	{
		$control = $this->iContactShopFactory->create();
		if ($this->model) {
			$control->setService();
			$control->setModel($this->model);
		}
		$control->onSend = function () {
			$this->flashMessage($this->translator->translate('Your request has been sent.'), 'success');
			$this->redirect('this', ['id' => NULL]);
		};
		return $control;
	}

	// </editor-fold>
}
