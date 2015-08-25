<?php

namespace App\AppModule\Presenters;

use App\Components\Newsletter\ISubscriberGridControlFactory;
use App\Components\Newsletter\SubscriberGridControl;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Facade\NewsletterFacade;
use DateTime;
use Nette\Application\UI;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

class SubscriberPresenter extends BasePresenter
{

	/** @var ISubscriberGridControlFactory @inject */
	public $iSubscriberGridControlFactory;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

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

	/** @return UI\Form */
	protected function createComponentForm()
	{
		$form = new UI\Form;
		$form->setRenderer(new MetronicFormRenderer)
				->setTranslator($this->translator);

		$form->addText('email', 'form.label.email')
				->setRequired()
				->addRule(Form::EMAIL);

		$form->addSelect('type', 'newsletter.admin.subscriber.grid.header.type', [
			Subscriber::TYPE_USER => 'newsletter.admin.subscriber.grid.types.user',
			Subscriber::TYPE_DEALER => 'newsletter.admin.subscriber.grid.types.dealer',
		]);

		$form->addSubmit('save', 'form.submit.save');
		$form->onSuccess[] = [$this, 'formSucceded'];
		return $form;
	}

	/**
	 * @param UI\Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceded(UI\Form $form, ArrayHash $values)
	{
		$subscriber = $this->newsletterFacade->findSubscriber($values->email, $values->type);

		if ($subscriber === NULL) {
			if ($values->type === Subscriber::TYPE_USER) {
				$this->newsletterFacade->subscribe($values->email);
			} elseif ($values->type === Subscriber::TYPE_DEALER) {
				$subscriber = new Subscriber;
				$subscriber->setMail($values->email)
						->setType(Subscriber::TYPE_DEALER)
						->setLocale('en')
						->setSubscribed(new DateTime);
				
				$this->em->persist($subscriber)
						->flush();
			}

			$this->flashMessage($this->translator->translate('newsletter.messages.addSubscriber.success'), 'success');
			$this->redirect('default');
		} else {
			$form->addError($this->translator->translate('newsletter.messages.addSubscriber.alreadyExists', ['email' => $values->email]));
		}
	}

}
