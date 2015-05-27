<?php

namespace Test\Model\Entity;

use App\Model\Entity\Group;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: User entity Groups
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserGroupsTest extends UserTestBase
{

	public function testSetAndGet()
	{
		$groupRepo = $this->em->getRepository(Group::class);
		
		$groupA = new Group('Group A');
		$groupB = new Group('Group B');
		$groupC = new Group('Group C');
		$this->em->persist($groupA);
		$this->em->persist($groupB);
		$this->em->persist($groupC);
		$this->em->flush();
		
		$group1 = $groupRepo->find(1);
		$group2 = $groupRepo->find(2);

		$this->user->mail = self::MAIL;
		$this->user->setGroups([$group1, $group2]);
		$this->saveUser();
		
		Assert::count(2, $this->user->groups);
		
		$group3 = $groupRepo->find(3);
		$this->user->addGroup($group3);
		$this->saveUser();
		
		Assert::count(3, $this->user->groups);
		
		$group4 = $groupRepo->find(3);
		$this->user->setGroups([$group4]);
		$this->saveUser();
		
		Assert::count(1, $this->user->groups);
		
		$this->user->setGroups([]);
		$this->saveUser();
		
		Assert::count(0, $this->user->groups);
	}

}

$test = new UserGroupsTest($container);
$test->run();
