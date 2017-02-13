<?php

namespace App\CronModule\Presenters;

use App\Mail\Messages\Basket\IUnfinishedMessageFactory;
use App\Model\Entity\Basket;
use App\Model\Repository\BasketRepository;
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

		/* @var $basketRepo BasketRepository */
		$basketRepo = $this->em->getRepository(Basket::getClassName());
		$baskets = $basketRepo->findUnfinished();

		if (count($baskets)) {
			foreach ($baskets as $basket) {
				/* @var $basket Basket */
				$basket->setShopVariant($this->shopVariant);
				if ($basket->user && $basket->user->locale) {
					$this->translator->setLocale($basket->user->locale);
				} else {
					$this->translator->setLocale($this->translator->getDefaultLocale());
				}

				if ($basket->mail) {
					$basket->setAccessHash();
					$message = $this->iUnfinishedMessageFactory->create();
					$message->addTo($basket->mail)
							->addParameter('basket', $basket)
							->addParameter('link', $this->link('//:Front:Cart:uncomplete', ['cart' => $basket->accessHash]));

					try {
						$message->send();
						$basket->sendedMailAt = new DateTime();
					} catch (SendException $e) {
						$this->status = parent::STATUS_ERROR;
						$this->message = 'One or more messages has not been sent, see log.';
						Debugger::log($e, ILogger::EXCEPTION);
					}
				}

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
