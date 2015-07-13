<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Page;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;

class ServicePresenter extends BasePresenter
{

	/** @var Page */
	private $page;

	public function actionDefault()
	{
		$serviceSettings = $this->moduleService->getModuleSettings('service');
		if (!$serviceSettings) {
			$this->flashMessage('This module isn\'t allowed.', 'warning');
			$this->redirect('Homepage:');
		}

		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->page = $pageRepo->find($serviceSettings->pageId);

		if (!$this->page) {
			$this->flashMessage('This page wasn\'t found.', 'warning');
			$this->redirect('Homepage:');
		}

		$this->page->setCurrentLocale($this->lang);
	}

	public function renderDefault($producer = NULL, $line = NULL, $model = NULL)
	{
		$this->template->page = $this->page;

		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());

		$producers = $producerRepo->findAll();
		$lines = [];
		$models = [];
		$activeProducer = NULL;
		$activeLine = NULL;
		$activeModel = NULL;

		if ($producer) {
			$activeProducer = $producerRepo->find($producer);
			if ($activeProducer) {
				$activeProducer->setCurrentLocale($this->lang);
				$lines = $activeProducer->lines;
			}
		}
		if ($line) {
			$activeLine = $lineRepo->find($line);
			if ($activeLine) {
				$models = $activeLine->models;
			}
		}
		if ($model) {
			$activeModel = $modelRepo->find($model);
			if ($activeModel) {
				$activeModel->setCurrentLocale($this->lang);
			}
		}

		$this->template->producers = $producers;
		$this->template->lines = $lines;
		$this->template->models = $models;
		$this->template->activeProducer = $activeProducer;
		$this->template->activeLine = $activeLine;
		$this->template->activeModel = $activeModel;

		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

}
