<?php

namespace App\FrontModule\Presenters;

use App\Components\Buyout\Form\IRequestFactory;
use App\Components\Buyout\Form\Request;
use App\Components\Producer\Form\ContactShop;
use App\Components\Producer\Form\IContactShopFactory;
use App\Model\Entity\Page;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;

class BuyoutPresenter extends BasePresenter
{

	/** @var Page */
	private $page;

	/** @var Producer */
	private $producer;

	/** @var ProducerLine */
	private $line;

	/** @var ProducerModel */
	private $model;

	/** @var IRequestFactory @inject */
	public $iRequestFactory;

	/** @var IContactShopFactory @inject */
	public $iContactShopFactory;

	public function actionDefault($producer = NULL, $line = NULL, $model = NULL)
	{
		$settings = $this->settings->modules->buyout;
		if (!$settings->enabled) {
			$message = $this->translator->translate('This module isn\'t allowed.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->page = $pageRepo->find($settings->pageId);

		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		if ($producer && $line && $model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$this->model = $modelRepo->findOneByUrl([$producer, $line, $model]);
			if ($this->model) {
				$this->model->setCurrentLocale($this->locale);
				$this->line = $this->model->line;
				$this->producer = $this->line->producer;
				$this->setView('model');
			} else {
				$message = $this->translator->translate('wasntFoundHe', NULL, ['name' => $this->translator->translate('Model')]);
			}
		} else if ($producer && $line) {
			$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
			$this->line = $lineRepo->findOneByUrl([$producer, $line]);
			if ($this->line) {
				$this->producer = $this->line->producer;
				$this->setView('line');
			} else {
				$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Line')]);
			}
		} else if ($producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$this->producer = $producerRepo->findOneByUrl([$producer]);
			if ($this->producer) {
				$this->setView('producer');
			} else {
				$message = $this->translator->translate('wasntFoundHe', NULL, ['name' => $this->translator->translate('Producer')]);
			}
		}

		if (isset($message)) {
			$this->flashMessage($message, 'warning');
			$this->redirect('this', ['producer' => NULL, 'line' => NULL, 'model' => NULL]);
		}

		$this->page->setCurrentLocale($this->locale);
	}

	public function renderDefault()
	{
		$this->renderAll($this->page);
	}

	public function renderProducer()
	{
		$this->renderAll($this->page . ' ' . $this->producer);
	}

	public function renderLine()
	{
		$this->renderAll($this->page . ' ' . $this->line);
	}

	public function renderModel()
	{
		$this->renderAll($this->page . ' ' . $this->model);
	}

	private function renderAll($seoText)
	{
		$this->changePageInfo(self::PAGE_INFO_TITLE, $seoText);
		$this->changePageInfo(self::PAGE_INFO_KEYWORDS, $seoText);
		$this->changePageInfo(self::PAGE_INFO_DESCRIPTION, $seoText);

		$this->template->page = $this->page;
		$this->template->producer = $this->producer;
		$this->template->line = $this->line;
		$this->template->model = $this->model;
	}

	/** @return Request */
	public function createComponentRequestForm()
	{
		$control = $this->iRequestFactory->create();
		$control->onSend = function () {
			$this->flashMessage($this->translator->translate('Your request has been sent.'), 'success');
			$this->redirect('this', ['producer' => NULL, 'line' => NULL, 'model' => NULL]);
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
			$this->redirect('this', ['producer' => NULL, 'line' => NULL, 'model' => NULL]);
		};
		return $control;
	}

}
