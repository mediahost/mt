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
use Nette\Utils\Strings;

abstract class BuyoutServiceBasePresenter extends BasePresenter
{

	/** @var array */
	protected $moduleSettings;

	/** @var Page */
	protected $page;

	/** @var Producer */
	protected $producer;

	/** @var ProducerLine */
	protected $line;

	/** @var ProducerModel */
	protected $model;

	/** @var IContactShopFactory @inject */
	public $iContactShopFactory;

	/** @var IRequestFactory @inject */
	public $iRequestFactory;

	protected function startup()
	{
		parent::startup();

		preg_match('/\w+$/', $this->getPresenter()->getName(), $matches);
		$modulName = Strings::lower($matches[0]);

		$this->moduleSettings = $this->settings->modules->$modulName;
		if (!$this->moduleSettings->enabled) {
			$message = $this->translator->translate('This module isn\'t allowed.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->page = $pageRepo->find($this->moduleSettings->pageId);

		if (!$this->page) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Page')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		$this->page->setCurrentLocale($this->locale);
	}

	public function actionDefault($producer = NULL, $line = NULL)
	{
		if ($producer && $line) {
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
			$this->redirect('default', ['producer' => NULL, 'line' => NULL]);
		}

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

	public function actionModel($model)
	{
		if ($model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$this->model = $modelRepo->findOneBySlug($model);
			if ($this->model) {
				$this->model->setCurrentLocale($this->locale);
				$this->line = $this->model->line;
				$this->producer = $this->line->producer;
				$this->setView('model');
			}
		}

		if (!$this->model) {
			$message = $this->translator->translate('wasntFoundHe', NULL, ['name' => $this->translator->translate('Model')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderModel()
	{
		$this->renderAll($this->page . ' ' . $this->model);
	}

	protected function renderAll($seoText)
	{
		$this->changePageInfo(self::PAGE_INFO_TITLE, $seoText);
		$this->changePageInfo(self::PAGE_INFO_KEYWORDS, $seoText);
		$this->changePageInfo(self::PAGE_INFO_DESCRIPTION, $seoText);

		$this->getTemplate()->page = $this->page;
		$this->getTemplate()->producer = $this->producer;
		$this->getTemplate()->line = $this->line;
		$this->getTemplate()->model = $this->model;
	}

	// <editor-fold desc="forms">

	/** @return ContactShop */
	public function createComponentContactShop()
	{
		$control = $this->iContactShopFactory->create();
		if ($this->model) {
			$control->setService();
			$control->setModel($this->model);
		}
		$control->onSend = function ($isBuyout, $isService) {
			$mesaage = $isService ? 'Your service request has been sent.' : 'Your buyout request has been sent.';
			$this->flashMessage($this->translator->translate($mesaage), 'success');
			$this->redirect('this', ['producer' => NULL, 'line' => NULL, 'model' => NULL]);
		};
		return $control;
	}

	/** @return Request */
	public function createComponentRequestForm()
	{
		$control = $this->iRequestFactory->create();
		$control->onSend = function () {
			$this->flashMessage($this->translator->translate('Your buyout request has been sent.'), 'success');
			$this->redirect('this', ['producer' => NULL, 'line' => NULL, 'model' => NULL]);
		};

		if ($this->model) {
			$control->setModel($this->model);
		}

		return $control;
	}

	// </editor-fold>

}
