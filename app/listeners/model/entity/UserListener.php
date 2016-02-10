<?php

namespace App\Listeners\Model\Entity;

use App\Extensions\Settings\SettingsStorage;
use App\Mail\Messages\Dealer\IDealerWantBeOurMessageFactory;
use App\Model\Entity\User;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Object;

class UserListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var IDealerWantBeOurMessageFactory @inject */
	public $createNotificationToShopMessage;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist(User $user)
	{
		if ($user->wantBeDealer) {
			$this->sendNotification($user);
		}
	}

	public function preUpdate(User $user)
	{
		if ($this->hasUserWantsBeDealer($user)) {
			$this->sendNotification($user);
		}
	}

	// </editor-fold>

	private function sendNotification(User $user)
	{
		$messageForShop = $this->createNotificationToShopMessage->create();
		$messageForShop->addParameter('dealer', $user->mail);
		$messageForShop->setFrom($user->mail);
		$messageForShop->send();
	}

	private function hasUserWantsBeDealer(User $entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('wantBeDealer', $changes)) {
			return $entity->wantBeDealer;
		}
		return FALSE;
	}

}
