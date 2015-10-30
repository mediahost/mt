<?php

namespace App\Model\Facade;

use App\Model\Entity\Newsletter\Message;
use App\Model\Entity\Newsletter\Status;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\User;
use DateTime;
use InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Http\Request;
use Nette\Object;
use Nette\Utils\Random;

class NewsletterFacade extends Object
{

	const TOKEN_ATTEMPTS = 5;
	const TOKEN_CHARLIST = 'a-z0-9';
	const TOKEN_LENGTH = 8;

	/** @var EntityManager @inject */
	public $em;

	/** @var Request @inject */
	public $request;

	/** @var Translator @inject */
	public $translator;

	/**
	 * @param User|string $identifier
	 */
	public function subscribe($identifier, $type = Subscriber::TYPE_USER)
	{
		if ($identifier instanceof User) {
			$user = $identifier;
			$subscriber = $user->subscriber;
		} elseif (is_string($identifier)) {
			$user = $this->em->getRepository(User::getClassName())->findOneByMail($identifier);
			$subscriber = $this->findSubscriber($identifier);
		} else {
			throw new InvalidArgumentException('Argument must be User or e-mail.');
		}

		if ($subscriber === NULL) {
			$subscriber = new Subscriber();
			$subscriber->mail = $identifier;
			$subscriber->token = $this->generateToken();
		}

		$subscriber->type = $type;
		$subscriber->ip = $this->request->getRemoteAddress();
		$subscriber->subscribed = new DateTime();
		$subscriber->locale = $this->translator->getLocale();

		if ($user !== NULL) {
			$user->setSubscriber($subscriber);
			$subscriber->setUser($user);
			$this->em->persist($user);
		}

		$this->em->persist($subscriber)
				->flush();
	}

	/**
	 * @param User|Subscriber|string $identifier
	 */
	public function unsubscribe($identifier)
	{
		if ($identifier instanceof Subscriber) {
			$subscriber = $identifier;
			$user = $identifier->user;
		} elseif ($identifier instanceof User) {
			$user = $identifier;
			$subscriber = $user->subscriber;

			if ($subscriber === NULL) {
				return;
			}
		} elseif (is_string($identifier)) {
			$subscriber = $this->findSubscriber($identifier);

			if ($subscriber === NULL) {
				return;
			}
			
			$user = $subscriber->user;
		} else {
			throw new InvalidArgumentException('Argument must be Subscriber, User or e-mail');
		}

		foreach ($subscriber->statuses as $status) {
			$status->subscriber = NULL;
		}

		if ($subscriber !== NULL) {
			$this->em->remove($subscriber);
		}

		if ($user !== NULL) {
			$user->subscriber = NULL;
			$this->em->persist($user);
		}

		$this->em->flush();
	}

	/**
	 * @param string $email
	 * @param int $type
	 * @return Subscriber|NULL
	 */
	public function findSubscriber($email, $type = Subscriber::TYPE_USER)
	{
		return $this->em->getRepository(Subscriber::getClassName())->findOneBy([
					'mail' => $email,
					'type' => $type,
		]);
	}

	/**
	 * @todo Change for to Do-While cycle.
	 * @return string
	 */
	public function generateToken()
	{
		for ($i = 0; $i < self::TOKEN_ATTEMPTS; $i++) {
			$generated = Random::generate(self::TOKEN_LENGTH, self::TOKEN_CHARLIST);

			$found = $this->em->getRepository(Subscriber::getClassName())->findOneBy([
				'token' => $generated,
			]);

			if ($found === NULL) {
				break;
			}
		}

		if ($found !== NULL) {
			throw new Exception('Problem with generating unsubscribtion token. Maximum attempts reached!');
		}

		return $generated;
	}

	/**
	 * @param Message $message
	 */
	public function pause(Message $message)
	{
		$this->em->beginTransaction();

		$message->status = Message::STATUS_PAUSED;
		$this->em->flush($message);

		$qb = $this->em->createQueryBuilder();

		$qb->update(Status::getClassName(), 's')
				->set('s.status', Message::STATUS_PAUSED)
				->where($qb->expr()->andX(
								$qb->expr()->eq('s.message', ':message'), $qb->expr()->eq('s.status', ':status')
						)
				)
				->setParameters([
					'message' => $message,
					'status' => Message::STATUS_RUNNING,
		]);

		$query = $qb->getQuery();

		$query->execute();

		$this->em->commit();
	}

	/**
	 * @param Message $message
	 */
	public function run(Message $message)
	{
		$this->em->beginTransaction();

		$message->status = Message::STATUS_RUNNING;
		$this->em->flush($message);

		$qb = $this->em->createQueryBuilder();

		$qb->update(Status::getClassName(), 's')
				->set('s.status', Message::STATUS_RUNNING)
				->where($qb->expr()->andX(
								$qb->expr()->eq('s.message', ':message'), $qb->expr()->eq('s.status', ':status')
						)
				)
				->setParameters([
					'message' => $message,
					'status' => Message::STATUS_PAUSED,
		]);

		$query = $qb->getQuery();

		$query->execute();

		$this->em->commit();
	}

}
