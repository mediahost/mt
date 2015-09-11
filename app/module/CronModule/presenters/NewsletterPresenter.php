<?php

namespace App\CronModule\Presenters;

use App\Mail\Messages\Newsletter\INewsletterMessageFactory;
use App\Model\Entity\Newsletter\Message;
use App\Model\Entity\Newsletter\Status;
use App\Model\Facade\NewsletterFacade;
use DateTime;
use Nette\Mail\SendException;
use Tracy\Debugger;
use Tracy\ILogger;

class NewsletterPresenter extends BasePresenter
{

	const DEFAULT_QUANTITY = 50;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var INewsletterMessageFactory @inject */
	public $iNewsletterMessageFactory;

	public function actionSend($quantity = self::DEFAULT_QUANTITY)
	{
		$this->status = parent::STATUS_OK;

		/* @var $statuses Status[] */
		$statuses = $this->em->getRepository(Status::getClassName())->findBy(['status' => Message::STATUS_RUNNING], [], $quantity);

		foreach ($statuses as $status) {
			if ($status->message->locale === NULL) {
				$this->translator->setLocale($status->subscriber->locale);
			} else {
				$this->translator->setLocale($status->message->locale);
			}

			$message = $this->iNewsletterMessageFactory->create();
			$message->addTo($status->email)
					->setSubject($status->message->subject)
					->addParameter('message', $status->message);

			if ($status->subscriber) {
				$message->addParameter('subscriber', $status->subscriber);
			}

			try {
				$message->send();
			} catch (SendException $e) {
				$this->status = parent::STATUS_ERROR;
				$this->message = 'One or more messages has not been sent, see log.';
				Debugger::log($e, ILogger::EXCEPTION);
			}

			$status->setSent(new DateTime)
					->setStatus(Message::STATUS_SENT);
			$this->em->flush($status);
		}
	}

}
