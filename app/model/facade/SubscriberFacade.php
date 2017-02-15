<?php

namespace App\Model\Facade;

use App\Model\Entity\Group;
use App\Model\Entity\Newsletter\Status;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\Shop;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use Nette\Object;

class SubscriberFacade extends Object
{

	const RECIPIENT_USER = 'u';
	const RECIPIENT_DEALER = 'd';

	/** @var EntityManager */
	private $em;

	/** @var EntityRepository */
	private $repository;

	/** @var Translator @inject */
	public $translator;

	/** @var LocaleFacade @inject */
	public $localeFacade;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->repository = $this->em->getRepository(Subscriber::getClassName());
	}

	public function findByType($type, $locale = NULL, Shop $shop = NULL)
	{
		$criteria['type'] = $type;
		if ($shop) {
			$criteria['shop'] = $shop;
		}
		if ($locale) {
			$criteria['locale'] = $locale;
		}

		return $this->repository->findBy($criteria);
	}

	public function countByType($type, $locale = NULL, Shop $shop = NULL)
	{
		$criteria['type'] = $type;
		if ($shop) {
			$criteria['shop'] = $shop;
		}
		if ($locale) {
			$criteria['locale'] = $locale;
		}

		return $this->repository->countBy($criteria);
	}

	/**
	 * @param mixed $recipient
	 * @return int
	 * @throws Exception
	 */
	public function counts($recipient)
	{
		$count = NULL;

		if ($recipient === self::RECIPIENT_USER) {
			$locales = $this->localeFacade->getLocalesToSelect();

			foreach ($locales as $locale => $label) {
				$count[$locale] = $this->countByType(Subscriber::TYPE_USER, $locale);
			}
		} elseif ($recipient === self::RECIPIENT_DEALER) {
			$count = $this->countByType(Subscriber::TYPE_DEALER);
		} elseif (is_numeric($recipient)) {
			/* @var \App\Model\Entity\Group $group */
			$group = $this->em->getRepository(Group::getClassName())->find($recipient);

			$count = count($group->users);
		} else if (preg_match('/^' . ShopFacade::PAIR_KEY_ALIAS . '(\w)$/', $recipient, $matches)) {
			$locales = $this->localeFacade->getLocalesToSelect();
			$shop = $this->em->getRepository(Shop::getClassName())->findOneBy([
				'priceLetter' => $matches[1],
			]);

			foreach ($locales as $locale => $label) {
				$count[$locale] = $this->countByType(Subscriber::TYPE_USER, $locale, $shop);
			}
		} else {
			throw new Exception('Unknown recipient type.');
		}

		return $count;
	}

	public function delete(Subscriber $subscriber)
	{
		$statusRepo = $this->em->getRepository(Status::getClassName());
		$statuses = $statusRepo->findBy(['subscriber' => $subscriber]);
		if (is_array($statuses)) {
			foreach ($statuses as $status) {
				$this->em->remove($status);
			}
			$this->em->flush();
		}

		if ($subscriber->user) {
			$subscriber->user->removeSubscriber();
			$this->em->persist($subscriber->user);
		}

		$this->em->remove($subscriber)->flush();
	}

}
