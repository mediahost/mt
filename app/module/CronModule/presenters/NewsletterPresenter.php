<?php

namespace App\CronModule\Presenters;

use App\Extensions\FilesManager;
use App\Helpers;
use App\Mail\Messages\Newsletter\INewsletterMessageFactory;
use App\Model\Entity\File;
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

	/** @var FilesManager @inject */
	public $filesManager;

	/** @var INewsletterMessageFactory @inject */
	public $iNewsletterMessageFactory;

	public function actionSend($quantity = self::DEFAULT_QUANTITY)
	{
		$this->status = parent::STATUS_OK;

		$statuses = $this->em->getRepository(Status::getClassName())->findBy(['status' => Message::STATUS_RUNNING], [], $quantity);

		if (count($statuses) > 0) {
			foreach ($statuses as $status) {
				/* @var $status Status */
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

				$mailRoot = $this->filesManager->getDir(FilesManager::MAILS);
				foreach ($status->message->attachments as $attachment) {
					/* @var $attachment File */
					$realFilename = Helpers::getPath($mailRoot, $attachment->filename);
					$message->addAttachment($realFilename);
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

			if ($this->status !== parent::STATUS_ERROR) {
				$this->message = 'All messages has been succesfully sent!';
			}
		} else {
			$this->message = 'There\'s nothing to send!';
		}
	}

	public function actionCheckMessageStatus()
	{
		$this->status = parent::STATUS_OK;

		$messages = $this->em->getRepository(Message::getClassName())->findBy([
			'status' => Message::STATUS_RUNNING,
		]);

		foreach ($messages as $message) {
			$statuses = $this->em->getRepository(Status::getClassName())->findBy([
				'message' => $message,
				'status' => Message::STATUS_RUNNING,
			]);

			if (count($statuses) === 0) {
				$message->status = Message::STATUS_SENT;
				$this->em->flush();
			}
		}
	}

}
