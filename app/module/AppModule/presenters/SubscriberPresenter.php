<?php

namespace App\AppModule\Presenters;

use App\Components\Newsletter\Form\ISubscriberEditFactory;
use App\Components\Newsletter\Form\SubscriberEdit;
use App\Components\Newsletter\Grid\ISubscriberGridFactory;
use App\Components\Newsletter\Grid\SubscriberGrid;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Facade\NewsletterFacade;
use App\Model\Facade\SubscriberFacade;

class SubscriberPresenter extends BasePresenter
{

	/** @var ISubscriberEditFactory @inject */
	public $iSubscriberEditFactory;

	/** @var ISubscriberGridFactory @inject */
	public $iSubscriberGridFactory;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var SubscriberFacade @inject */
	public $subscriberFacade;

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		
	}

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('delete')
	 */
	public function handleDelete($id)
	{
		$subscriber = $this->em->getRepository(Subscriber::getClassName())->find($id);

		if ($subscriber === NULL) {
			$this->flashMessage($this->translator->translate('newsletter.messages.handleDelete.notFound', ['id' => $id]), 'warning');
		} else {
			$this->subscriberFacade->delete($subscriber);
			$this->flashMessage($this->translator->translate('newsletter.messages.handleDelete.success', ['email' => $subscriber->mail]), 'success');
		}

		$this->redirect('default');
	}

	/** @return SubscriberGrid */
	protected function createComponentGrid()
	{
		return $this->iSubscriberGridFactory->create();
	}

	/** @return SubscriberEdit */
	protected function createComponentForm()
	{
		$control = $this->iSubscriberEditFactory->create();
		$control->onAfterSave = function () {
			$this->redirect('default');
		};
		return $control;
	}

}
