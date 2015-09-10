<?php

namespace App\CronModule\Presenters;

use App\Mail\Messages\Newsletter\INewsletterMessageFactory;
use App\Model\Entity\Newsletter\Status;
use App\Model\Facade\NewsletterFacade;
use Nette\Mail\SendException;

class NewsletterPresenter extends BasePresenter
{

	const DEFAULT_QUANTITY = 50;
	const LOGNAME = 'pohoda_cron';

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var INewsletterMessageFactory @inject */
	public $iNewsletterMessageFactory;

	public function actionSend($quantity = self::DEFAULT_QUANTITY)
	{
		$statuses = $this->em->getRepository(Status::getClassName())->findBy([], [], $quantity);

		foreach ($statuses as $status) {
			try {
				$message = $this->iNewsletterMessageFactory->create();
				$message->addTo($status->email)
						->setSubject($status->message->subject)
						->addParameter('content', $status->message->content);
						
				if ($status->subscriber) {
					$message->addParameter('subscriber', $status->subscriber);
				}
				
				$message->send();
			} catch (SendException $e) {
				
			}

			\Tracy\Debugger::barDump($status->email);
		}
		$this->status = parent::STATUS_OK;
	}

}
