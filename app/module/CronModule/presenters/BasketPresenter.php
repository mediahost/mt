<?php

namespace App\CronModule\Presenters;

use App\Mail\Messages\Basket\IUnfinishedMessageFactory;
use App\Model\Entity\Basket;
use DateTime;
use Nette\Mail\SendException;
use Tracy\Debugger;
use Tracy\ILogger;

class BasketPresenter extends BasePresenter
{

	/** @var IUnfinishedMessageFactory @inject */
	public $iUnfinishedMessageFactory;

	public function actionUnfinishedCheck()
	{
		$this->status = parent::STATUS_OK;

		$basketRepo = $this->em->getRepository(Basket::getClassName());
		$baskets = $basketRepo->findAllUnfinished();

		if (count($baskets)) {
			foreach ($baskets as $basket) {
				/* @var $basket Basket */
				if ($basket->user && $basket->user->locale) {
					$this->translator->setLocale($basket->user->locale);
					$mail = $basket->user->mail;
				} else {
					$this->translator->setLocale($this->translator->getDefaultLocale());
					$mail = $basket->mail;
				}

				if ($mail) {
					$mail = 'info@mediahost.sk';
					$basket->setAccessHash();
					$message = $this->iUnfinishedMessageFactory->create();
					$message->addTo($mail)
							->addParameter('basket', $basket)
							->addParameter('link', $this->link('//:Front:Cart:uncomplete', ['cart' => $basket->accessHash]));

					try {
						$message->send();
					} catch (SendException $e) {
						$this->status = parent::STATUS_ERROR;
						$this->message = 'One or more messages has not been sent, see log.';
						Debugger::log($e, ILogger::EXCEPTION);
					}
				}

				$basket->sendedMailAt = new DateTime();
				$this->em->flush($basket);
			}

			if ($this->status === parent::STATUS_ERROR) {
				$this->message = 'All messages has been succesfully sent!';
			}
		} else {
			$this->message = 'There\'s nothing to send!';
		}
	}

}
