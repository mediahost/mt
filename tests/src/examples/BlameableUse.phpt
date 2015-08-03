<?php

namespace Test\Examples;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Entity\UserCallable;
use App\Model\Facade\RoleFacade;
use Kdyby\Doctrine\EntityRepository;
use Knp\DoctrineBehaviors\ORM\Blameable\BlameableSubscriber;
use Test\Examples\Model\Entity\Blameable;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Blameable use
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/Knp/DoctrineBehaviors/ORM/BlameableTest.php
 *
 * @testCase
 * @phpVersion 5.4
 */
class BlameableUseTest extends BaseUse
{

	const USER = 'user1';

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var EntityRepository */
	private $blameableRepo;

	/** @var BlameableSubscriber */
	private $subscriber;

	protected function init($user = null, callable $userCallback = null, $userEntity = null)
	{
		$this->subscriber = $this->getContainer()->getService('blameableSubscriber');
		$this->subscriber->setUser($user);
		$this->subscriber->setUserEntity($userEntity);
		if ($userCallback) {
			$this->subscriber->setUserCallable($userCallback);
		}

		$this->blameableRepo = $this->em->getRepository(Blameable::getClassName());
		$this->updateSchema();

		$this->roleFacade->create(Role::GUEST);
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->dropSchema();
	}

	public function testCreate()
	{
		$this->init(self::USER, NULL, NULL);

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();

		Assert::same(self::USER, $entity->createdBy);
		Assert::same(self::USER, $entity->updatedBy);
		Assert::null($entity->deletedBy);
	}

	public function testUpdate()
	{
		$this->init(self::USER, NULL, NULL);

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();
		$id = $entity->id;
		$createdBy = $entity->createdBy;
		$this->em->clear();

		$subscribers = $this->em->getEventManager()->getListeners()['preUpdate'];
		$subscriber = array_pop($subscribers);
		$subscriber->setUser('user2');

		$findedEntity1 = $this->blameableRepo->find($id);
		$findedEntity1->name = 'test';
		$this->em->flush();
		$this->em->clear();

		$findedEntity2 = $this->blameableRepo->find($id);
		Assert::same($createdBy, $findedEntity2->createdBy);
		Assert::same('user2', $findedEntity2->updatedBy);
		Assert::notEqual($findedEntity2->createdBy, $findedEntity2->updatedBy);
	}

	public function testRemove()
	{
		$this->init(self::USER, NULL, NULL);

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();
		$id = $entity->id;
		$this->em->clear();

		$subscribers = $this->em->getEventManager()->getListeners()['preUpdate'];
		$subscriber = array_pop($subscribers);
		$subscriber->setUser('user3');

		$findedEntity = $this->blameableRepo->find($id);
		$this->em->remove($findedEntity);
		$this->em->flush();
		$this->em->clear();

		Assert::same('user3', $findedEntity->deletedBy);
	}

	public function testSubscriberWithUserCallback()
	{
		$user1 = new User('user1@mail.com');
		$user2 = new User('user2@mail.com');

		$user1Callback = function() use($user1) {
			return $user1;
		};

		$this->init(NULL, $user1Callback, User::class);

		$this->em->persist($user1);
		$this->em->persist($user2);
		$this->em->flush();

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();
		$id = $entity->id;
		$createdBy = $entity->createdBy;
		$this->subscriber->setUser($user2); // switch user for update

		$findedEntity = $this->blameableRepo->find($id);
		$findedEntity->name = 'other name';
		$this->em->flush();
		$this->em->clear();

		Assert::type(User::class, $findedEntity->createdBy);
		Assert::same($createdBy, $findedEntity->createdBy);
		Assert::same($user2, $findedEntity->updatedBy);
		Assert::notEqual($findedEntity->createdBy, $findedEntity->updatedBy);
	}

	public function testSubscriberWithUnsignedIdentityCallback()
	{
		$userCallback = new UserCallable($this->getContainer());

		$this->init(NULL, $userCallback, User::class);

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();

		Assert::null($entity->createdBy);
	}

	public function testSubscriberWithSignedIdentityCallback()
	{
		$userCallback = new UserCallable($this->getContainer());

		$this->init(NULL, $userCallback, User::class);

		$user = new User('user@mail.com');
		$this->em->persist($user);
		$this->em->flush();

		$identity = $this->getContainer()->getService('security.user');
		$identity->login($user);

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();
		$id = $entity->id;
		$this->em->clear();

		$findedEntity = $this->blameableRepo->find($id);

		Assert::same($user->id, $findedEntity->createdBy->id);
		Assert::same($user->mail, $findedEntity->createdBy->mail);

		$identity->logout();
	}

	public function testShould_only_persist_user_entity()
	{
		$user = new User('user@mail.com');
		$userCallback = function() use($user) {
			return $user;
		};

		$this->init('anonymouse', $userCallback, User::class);

		$this->em->persist($user);
		$this->em->flush();

		$entity = new Blameable();

		$this->em->persist($entity);
		$this->em->flush();

		Assert::null($entity->createdBy);
		Assert::null($entity->updatedBy);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Blameable::getClassName()),
			$this->em->getClassMetadata(User::getClassName()),
			$this->em->getClassMetadata(Role::getClassName()),
		];
	}

}

$test = new BlameableUseTest($container);
$test->run();
