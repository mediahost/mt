<?php

namespace App\Components\Newsletter\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Mail\Messages\Newsletter\INewsletterMessageFactory;
use App\Model\Entity\Group;
use App\Model\Entity\Newsletter\Message;
use App\Model\Entity\Newsletter\Status;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Facade\LocaleFacade;
use App\Model\Facade\NewsletterFacade;
use App\Model\Facade\SubscriberFacade;
use Nette\Http\Request;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

class NewsletterMessageEdit extends BaseControl
{

	const LOCALE_DOMAIN = 'newsletter.admin.newsletter';
	const DEFAULT_LOCALE_DEALER = 'en';
	const DEFAULT_LOCALE_GROUP = 'sk';

	private $recipientsValue = SubscriberFacade::RECIPIENT_USER;
	private $localeValue;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @var Request @inject */
	public $request;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var INewsletterMessageFactory @inject */
	public $iNewsletterMail;

	/** @var LocaleFacade @inject */
	public $localeFacade;

	/** @var SubscriberFacade @inject */
	public $subscriberFacade;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setRenderer(new MetronicFormRenderer())
				->setTranslator($this->translator);

		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findPairs('name', [], 'id');

		$recipients = [
			SubscriberFacade::RECIPIENT_USER => self::LOCALE_DOMAIN . '.form.users',
			SubscriberFacade::RECIPIENT_DEALER => self::LOCALE_DOMAIN . '.form.dealers',
			self::LOCALE_DOMAIN . '.form.groups' => $groups,
		];
		$form->addSelect('recipients', self::LOCALE_DOMAIN . '.form.recipients', $recipients)
				->setDefaultValue($this->recipientsValue)
				->getControlPrototype()
				->addAttributes(['id' => 'new-newsletter-recipients']);

		$locales = $this->localeFacade->getLocalesToSelect();
		$form->addSelect('locale', self::LOCALE_DOMAIN . '.form.locale', $locales)
				->getControlPrototype()
				->addAttributes(['id' => 'new-newsletter-locale']);

		$form->addText('subject', self::LOCALE_DOMAIN . '.form.subject')
				->setRequired();

		$form->addWysiHtml('content', self::LOCALE_DOMAIN . '.form.content', 20)
						->setRequired()
						->getControlPrototype()->class[] = 'page-html-content';

		$testMail = $form->addText('testEmail', self::LOCALE_DOMAIN . '.form.testEmail');

		$form->addSubmit('send', self::LOCALE_DOMAIN . '.form.send');
		$form->addSubmit('sendTest', self::LOCALE_DOMAIN . '.form.sendTest')
						->getControlPrototype()->class[] = 'ajax';

		$testMail->addConditionOn($form['sendTest'], Form::SUBMITTED)
				->addRule(Form::EMAIL);

		$form->addSubmit('validate', self::LOCALE_DOMAIN . '.form.validate')
						->setValidationScope(FALSE)
						->getControlPrototype()
						->addAttributes(['id' => 'new-newsletter-validate'])->class[] = 'ajax hidden';

		$form->onSuccess[] = $this->formSucceeded;

		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		if ($form['validate']->isSubmittedBy()) {
			$this->validateAction($form, $values);
		}
		if ($form['sendTest']->isSubmittedBy()) {
			$this->sendTestMail($form, $values);
			
			$flash = $this->translator->translate('newsletter.messages.newMessage.testSuccess', ['recipient' => $values->testEmail]);
			$this->flashMessage($flash, 'success');
		}
		if ($form['send']->isSubmittedBy()) {
			$this->createNewsletterMessage($form, $values);
			
			$this->flashMessage($this->translator->translate('newsletter.messages.newMessage.queue'), 'success');
			$this->onAfterSave();
		}

		if ($this->presenter->ajax) {
			$this->redrawControl();
		}
	}

	private function validateAction(Form $form, ArrayHash $values)
	{
		$this->recipientsValue = $values->recipients;
		if ($this->recipientsValue === SubscriberFacade::RECIPIENT_USER) {
			$this->localeValue = $values->locale;
		} elseif ($this->recipientsValue === SubscriberFacade::RECIPIENT_DEALER) {
			$this->localeValue = self::DEFAULT_LOCALE_DEALER;
			$form['locale']->setDisabled(TRUE);
		} elseif (is_numeric($this->recipientsValue)) {
			$this->localeValue = self::DEFAULT_LOCALE_GROUP;
		}
		$form['locale']->setValue($this->localeValue);
	}

	private function sendTestMail(Form $form, ArrayHash $values)
	{
		$message = new Message();
		$message->setContent($values->content);

		$mail = $this->iNewsletterMail->create();
		$mail->addTo($values->testEmail)
				->setSubject($values->subject)
				->addParameter('message', $message)
				->send();
	}

	private function createNewsletterMessage(Form $form, ArrayHash $values)
	{
		$message = new Message();
		$message->setSubject($values->subject)
				->setContent($values->content)
				->setStatus(Message::STATUS_RUNNING)
				->setCreated(new DateTime())
				->setLocale($values->locale);

		$this->em->persist($message);

		if ($values->recipients === SubscriberFacade::RECIPIENT_USER) {

			$message->setType(Message::TYPE_USER);
			$recipients = $this->subscriberFacade->findByType(Subscriber::TYPE_USER, $values->locale);
		} else if ($values->recipients === SubscriberFacade::RECIPIENT_DEALER) {

			$message->setType(Message::TYPE_DEALER)
					->setUnsubscribable(FALSE);
			$recipients = $this->subscriberFacade->findByType(Subscriber::TYPE_DEALER);
		} else if (is_numeric($values->recipients)) {
			$message->setType(Message::TYPE_GROUP)
					->setUnsubscribable(FALSE);

			/* @var Group $group */
			$groupRepo = $this->em->getRepository(Group::getClassName());
			$group = $groupRepo->find($values->recipients);
			$message->group = $group;

			$recipients = $group->users;
		} else {
			throw new BaseControlException('Unknown recipient type.');
		}

		foreach ($recipients as $recipient) {
			$status = new Status();
			$status->setEmail($recipient->mail)
					->setMessage($message)
					->setStatus(Message::STATUS_RUNNING);

			if ($values->recipients === SubscriberFacade::RECIPIENT_USER) {
				$status->subscriber = $recipient;
			}

			$this->em->persist($status);
		}

		$this->em->flush();
	}

	public function render()
	{
		$counts = $this->subscriberFacade->counts($this->recipientsValue);
		if (is_array($counts)) {
			$this->template->count = $counts[$this->localeValue];
			$this->template->data = Json::encode($counts);
		} else {
			$this->template->count = $counts;
		}

		parent::render();
	}

}

interface INewsletterMessageEditFactory
{

	/** @return NewsletterMessageEdit */
	function create();
}
