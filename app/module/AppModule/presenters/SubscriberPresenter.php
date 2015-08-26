<?php


namespace App\AppModule\Presenters;

use App\Components\Newsletter\ISubscriberGridControlFactory;
use App\Components\Newsletter\SubscriberGridControl;
use App\Model\Entity\Newsletter\Subscriber;

class SubscriberPresenter extends BasePresenter
{
	/** @var ISubscriberGridControlFactory @inject */
	public $iSubscriberGridControlFactory;

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
			if ($subscriber->user) {
				$subscriber->user->subscriber = NULL;
				$this->em->persist($subscriber->user);
			}
			
			$this->em->remove($subscriber)
					->flush();
			
			$this->flashMessage($this->translator->translate('newsletter.messages.handleDelete.success', ['email' => $subscriber->mail]), 'success');
		}
		
		$this->redirect('default');
	}

	/** @return SubscriberGridControl */
	protected function createComponentGrid()
	{
		return $this->iSubscriberGridControlFactory->create();
	}

}
