<?php

namespace App\FrontModule\Presenters;

use App\Components\Buyout\Form\IRequestFactory;
use App\Components\Buyout\Form\Request;
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

	/** @var ProducerModel */
	private $model;

	/** @persistent */
	public $modelId;

	public function actionDefault($modelId = NULL)
	{
		if ($modelId !== NULL) {
			$this->modelId = $modelId;
			$this->model = $this->em->getRepository(ProducerModel::getClassName())->find($modelId);
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
	}

	/** @return ModelSelector */
	public function createComponentModelSelector()
	{
		$control = $this->iModelSelectorFactory->create();
		$control->setAjax()
				->setBuyout();

		$control->onAfterSelect = function ($producer, $line, $model) {
			$this->model = $model;
			$this->modelId = $model->id;

			if ($this->isAjax()) {
				$this->redrawControl();
			} else {
				$this->redirect('this', ['modelId' => $this->model->id]);
			}
		};
		return $control;
	}

	/** @return Request */
	public function createComponentRequestForm()
	{
		$control = $this->iRequestFactory->create();
		$control->onSend = function () {
			$this->session->getSection('buyout')->modelId = NULL;
			$this->flashMessage($this->translator->translate('Your request has been sent.'), 'success');
			$this->redirect('this', ['modelId' => NULL]);
		};

		if ($this->model) {
			$this->modelId = $this->model->id;
			$control->setModel($this->model);
		}

		return $control;
	}

}
