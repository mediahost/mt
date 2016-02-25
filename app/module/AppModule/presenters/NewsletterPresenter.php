<?php

namespace App\AppModule\Presenters;

use App\Components\Newsletter\Form\INewsletterMessageEditFactory;
use App\Components\Newsletter\Form\NewsletterMessageEdit;
use App\Components\Newsletter\Grid\IMessageGridFactory;
use App\Components\Newsletter\Grid\MessageGrid;

class NewsletterPresenter extends BasePresenter
{

	/** @var IMessageGridFactory @inject */
	public $iMessageGridFactory;

	/** @var INewsletterMessageEditFactory @inject */
	public $iNewsletterMessageEdit;

	/**
	 * @secured
	 * @resource('newsletter')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('newsletter')
	 * @privilege('new')
	 */
	public function actionNew()
	{
		
	}

	/** @return MessageGrid */
	protected function createComponentGrid()
	{
		return $this->iMessageGridFactory->create();
	}

	/** @return NewsletterMessageEdit */
	protected function createComponentForm()
	{
		$control = $this->iNewsletterMessageEdit->create();
		$control->onAfterSave = function () {
			$this->redirect('default');
		};
		return $control;
	}

}
