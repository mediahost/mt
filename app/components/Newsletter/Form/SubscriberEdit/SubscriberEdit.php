<?php

namespace App\Components\Newsletter\Form;

use App\Components\BaseControl;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Facade\NewsletterFacade;
use Nette\Application\UI\Form;
use Nette\Http\Request;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class SubscriberEdit extends BaseControl
{

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @var Request @inject */
	public $request;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setRenderer(new MetronicFormRenderer())
				->setTranslator($this->translator);

		$form->addText('email', 'form.label.email')
				->setRequired()
				->addRule(Form::EMAIL);

		$type = [
			Subscriber::TYPE_USER => 'newsletter.admin.subscriber.grid.types.user',
			Subscriber::TYPE_DEALER => 'newsletter.admin.subscriber.grid.types.dealer',
		];
		$form->addSelect('type', 'newsletter.admin.subscriber.grid.header.type', $type);

		$form->addSubmit('save', 'form.submit.save');
		$form->onSuccess[] = $this->formSucceeded;

		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$subscriber = $this->newsletterFacade->findSubscriber($values->email, $values->type);

		if ($subscriber === NULL) {
			if ($values->type === Subscriber::TYPE_USER) {
				$this->newsletterFacade->subscribe($values->email);
			} elseif ($values->type === Subscriber::TYPE_DEALER) {
				$subscriber = new Subscriber();
				$subscriber->setMail($values->email)
						->setType(Subscriber::TYPE_DEALER)
						->setLocale('en')
						->setSubscribed(new DateTime);

				$this->em->persist($subscriber)
						->flush();
			}

			$message = $this->translator->translate('newsletter.messages.addSubscriber.success');
			$this->flashMessage($message, 'success');
			$this->onAfterSave($subscriber);
		} else {
			$message = $this->translator->translate('newsletter.messages.addSubscriber.alreadyExists', ['email' => $values->email]);
			$form->addError($message);
		}
	}

}

interface ISubscriberEditFactory
{

	/** @return SubscriberEdit */
	function create();
}
