<?php

namespace App\FrontModule\Presenters;

use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Mail\Messages\Newsletter\IUnsubscribeMessageFactory;
use App\Model\Entity\Newsletter\Message;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Facade\NewsletterFacade;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class NewsletterPresenter extends BasePresenter
{

	/** @var EntityManager @inject */
	public $em;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var IUnsubscribeMessageFactory @inject */
	public $iUnsubscribeMessageFactory;

	/**
	 * @param string $email
	 * @param string $token
	 */
	public function actionUnsubscribe($email = NULL, $token = NULL)
	{
		if ($email !== NULL) {
			if ($token !== NULL) {
				$subscriber = $this->em->getRepository(Subscriber::getClassName())->findOneBy([
					'mail' => $email,
					'token' => $token,
					'type' => Subscriber::TYPE_USER,
				]);

				if ($subscriber !== NULL) {
					$this->newsletterFacade->unsubscribe($email);
					$this->flashMessage($this->translator->translate('newsletter.presenter.success'), 'success');
					$this->redirect('Homepage:');
				} else {
					$this->flashMessage($this->translator->translate('newsletter.presenter.tokenNotMatch'), 'warning');
					$this->redirect('this', ['email' => $email, 'token' => NULL]);
				}
			} else {
				$this['unsubscribeForm']->setDefaults([
					'email' => $email,
				]);
			}
		}
	}

	/**
	 * @param int $id Message identifier
	 */
	public function actionShow($id)
	{
		$message = $this->em->getRepository(Message::getClassName())->find($id);

		if (!$message) {
			throw new BadRequestException;
		}

		$path = [__DIR__, '..', '..', '..' , 'mail', 'messages', 'Newsletter', 'NewsletterMessage', 'NewsletterMessage.latte'];
		$this->template->setFile(implode(DIRECTORY_SEPARATOR, $path));
		
		$this->template->settings = $this->settings->modules->newsletter;
		$this->template->pageInfo = $this->settings->pageInfo;
		$this->template->mail = $message;
		$this->template->message = $message;
		$this->template->colon = ':';
	}

	protected function createComponentUnsubscribeForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator->domain('newsletter.unsubscribeForm'))
				->setRenderer(new MetronicHorizontalFormRenderer());

		$form->addText('email', 'label');

		$form->addSubmit('unsubscibe', 'submit');

		$form->onSuccess[] = [$this, 'unsubscribeFormSucceeded'];
		return $form;
	}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function unsubscribeFormSucceeded(Form $form, ArrayHash $values)
	{
		$subscriber = $this->newsletterFacade->findSubscriber($values->email);
		$message = $this->iUnsubscribeMessageFactory->create();

		if ($subscriber !== NULL) {
			$message->addParameter('link', $this->link('//:Front:Newsletter:unsubscribe', ['email' => $subscriber->mail, 'token' => $subscriber->token]));
		}

		$message->addTo($values->email)
				->addParameter('subscriber', $subscriber)
				->send();

		$this->flashMessage($this->translator->translate('newsletter.unsubscribeForm.emailSent'));
		$this->redirect('Homepage:');
	}

}
