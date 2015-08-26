<?php

namespace App\FrontModule\Presenters;

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

	// </editor-fold>

	public function actionDefault()
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

		$this->page->setCurrentLocale($this->locale);
	}

	public function renderDefault()
	{
		$this->template->page = $this->page;

		$producerRepo = $this->em->getRepository(Producer::getClassName());

		$producers = $producerRepo->findAll();
		$producersTree = [];
		foreach ($producers as $producerItem) {
			$linesTree = [];
			if (!$producerItem->hasLines) {
				continue;
			}
			foreach ($producerItem->lines as $lineItem) {
				if (!$lineItem->hasModels) {
					continue;
				}
				$modelsTree = [];
				foreach ($lineItem->models as $modelItem) {
					$modelsTree[$modelItem->id] = [
						'name' => (string) $modelItem,
					];
				}
				$linesTree[$lineItem->id] = [
					'name' => (string) $lineItem,
					'children' => $modelsTree,
				];
			}
			$producersTree[$producerItem->id] = [
				'name' => (string) $producerItem,
				'children' => $linesTree,
			];
		}

		$this->template->producersTree = $producersTree;
		$this->template->producers = $producers;
		if ($this->model instanceof ProducerModel) {
			$this->model->setCurrentLocale($this->locale);
		}
		$this->template->model = $this->model;

		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

	// <editor-fold desc="forms">

	/** @return ModelSelector */
	public function createComponentModelSelector()
	{
		$control = $this->iModelSelectorFactory->create();
		$control->setAjax();
		$control->onAfterSelect = function ($producer, $line, $model) {
			$this->model = $model;
			if ($this->isAjax()) {
				$this->redrawControl();
			}
		};
		return $control;
	}

	// </editor-fold>
}
