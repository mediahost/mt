<?php

namespace App\AppModule\Presenters;

use App\Components\Newsletter\IMessageGridControlFactory;
use App\Components\Newsletter\MessageGridControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Mail\Messages\Newsletter\INewsletterMessageFactory;
use App\Model\Entity\Group;
use App\Model\Entity\Newsletter\Message;
use App\Model\Entity\Newsletter\Status;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Facade\LocaleFacade;
use App\Model\Facade\SubscriberFacade;
use DateTime;
use Exception;
use Nette\Application\UI;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\ArrayHash;

class NewsletterPresenter extends BasePresenter
{

	const LOCALE_DOMAIN = 'newsletter.admin.newsletter';
	const RECIPIENT_USER = 'u';
	const RECIPIENT_DEALER = 'd';
	const DEFAULT_LOCALE_DEALER = 'en';
	const DEFAULT_LOCALE_GROUP = 'sk';

	/** @var IMessageGridControlFactory @inject */
	public $iMessageGridControlFactory;

	/** @var INewsletterMessageFactory @inject */
	public $iNewsletterMessage;

	/** @var LocaleFacade @inject */
	public $localeFacade;

	/** @var SubscriberFacade @inject */
	public $subscriberFacade;

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

	/** @return MessageGridControl */
	protected function createComponentGrid()
	{
		return $this->iMessageGridControlFactory->create();
	}

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setRenderer(new MetronicFormRenderer)
				->setTranslator($this->translator);

		$groups = $this->em->getRepository(Group::getClassName())->findPairs('name', [], 'id');
		$recipients = [
			self::RECIPIENT_USER => self::LOCALE_DOMAIN . '.form.users',
			self::RECIPIENT_DEALER => self::LOCALE_DOMAIN . '.form.dealers',
			self::LOCALE_DOMAIN . '.form.groups' => $groups,
		];

		$form->addSelect('recipients', self::LOCALE_DOMAIN . '.form.recipients', $recipients)
				->getControlPrototype()
				->addAttributes(['id' => 'new-newsletter-recipients']);

		$form->addSelect('locale', self::LOCALE_DOMAIN . '.form.locale', $this->localeFacade->getLocalesToSelect())
				->setDisabled($form['recipients']->value === self::RECIPIENT_DEALER);

		$form->addText('subject', self::LOCALE_DOMAIN . '.form.subject')
				->setRequired();

		$form->addWysiHtml('content', self::LOCALE_DOMAIN . '.form.content', 20)
						->setRequired()
						->getControlPrototype()->class[] = 'page-html-content';

		$form->addSubmit('send', self::LOCALE_DOMAIN . '.form.send');

		$form->addSubmit('sendTest', self::LOCALE_DOMAIN . '.form.sendTest')
						->getControlPrototype()->class[] = 'ajax';

		$testEmail = new TextInput(self::LOCALE_DOMAIN . '.form.testEmail');
		$testEmail->addConditionOn($form['sendTest'], Form::SUBMITTED)
				->addRule(Form::EMAIL);

		$form->addComponent($testEmail, 'testEmail', 'send');

		$form->addSubmit('validate', self::LOCALE_DOMAIN . '.form.validate')
						->setValidationScope(FALSE)
						->getControlPrototype()
						->addAttributes(['id' => 'new-newsletter-validate'])->class[] = 'ajax hidden';

		$form->onSuccess[] = [$this, 'formSucceded'];
		return $form;
	}

	/**
	 * @param UI\Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceded(UI\Form $form, ArrayHash $values)
	{

		if ($form['validate']->isSubmittedBy()) {
			if ($values->recipients === self::RECIPIENT_USER) {
				$locale = isset($values->locale) ? $values->locale : NULL;
			} elseif ($values->recipients === self::RECIPIENT_DEALER) {
				$locale = self::DEFAULT_LOCALE_DEALER;
			} elseif (is_numeric($values->recipients)) {
				$locale = self::DEFAULT_LOCALE_GROUP;
			} else {
				$locale = NULL;
			}

			$form->setValues(['locale' => $locale]);
		} elseif ($form['sendTest']->isSubmittedBy()) {
			$message = $this->iNewsletterMessage->create();
			$message->addTo($values->testEmail)
					->setSubject($values->subject)
					->addParameter('content', $values->content)
					->send();

			$this->flashMessage($this->translator->translate('newsletter.messages.newMessage.testSuccess', ['recipient' => $values->testEmail]), 'success');
		} elseif ($form['send']->isSubmittedBy()) {
			$message = new Message;
			$message->setSubject($values->subject)
					->setContent($values->content)
					->setStatus(Status::STATUS_RUNNIG)
					->setCreated(new DateTime)
					->setLocale($values->locale);

			$this->em->persist($message);

			if ($values->recipients === self::RECIPIENT_USER) {
				$message->setType(Message::TYPE_USER);
				$recipients = $this->subscriberFacade->findByType(Subscriber::TYPE_USER, $values->locale);
			} elseif ($values->recipients === self::RECIPIENT_DEALER) {
				$message->setType(Message::TYPE_DEALER)
						->setUnsubscribable(FALSE);
				$recipients = $this->subscriberFacade->findByType(Subscriber::TYPE_DEALER);
			} elseif (is_numeric($values->recipients)) {
				$message->setType(Message::TYPE_GROUP)
						->setUnsubscribable(FALSE);

				/* @var \App\Model\Entity\Group $group */
				$group = $this->em->getRepository(Group::getClassName())->find($values->recipients);
				$message->group = $group;

				$recipients = $group->users;
			} else {
				throw new Exception('Unknown recipient type.');
			}

			foreach ($recipients as $recipient) {
				$status = new Status;
				$status->setMail($recipient->mail)
						->setMessage($message)
						->setStatus(Status::STATUS_RUNNIG);
				$this->em->persist($status);
			}

			$this->em->flush();

			$this->flashMessage($this->translator->translate('newsletter.messages.newMessage.queue'), 'success');
			$this->redirect('default');
		}

		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

}
