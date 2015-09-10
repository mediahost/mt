<?php

namespace Test\Model\Facade;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Repository\RepositoryException;
use App\Model\Repository\UserRepository;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: UserRepository
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserRepositoryTest extends BaseRepository
{

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserRepository */
	protected $repository;

	function __construct(Container $container = NULL)
	{
		parent::__construct($container);
		$this->repository = $this->em->getRepository(User::getClassName());
	}

	protected function setUp()
	{
		parent::setUp();
		$this->importDbDataFromFile(__DIR__ . '/sql/init_users.sql');
	}

	public function testFindPairsByRoleId()
	{
		$roleRepo = $this->em->getRepository(Role::getClassName());
		$roleSigned = $roleRepo->findOneByName(Role::SIGNED);
		$roleUser = $roleRepo->findOneByName(Role::USER);
		$roleDealer = $roleRepo->findOneByName(Role::DEALER);
		$roleAdmin = $roleRepo->findOneByName(Role::ADMIN);
		$roleSuperadmin = $roleRepo->findOneByName(Role::SUPERADMIN);

		$signedMails = $this->repository->findPairsByRoleId($roleSigned->id, 'mail');
		$usersMails = $this->repository->findPairsByRoleId($roleUser->id, 'mail');
		$dealersMails = $this->repository->findPairsByRoleId($roleDealer->id, 'mail');
		$adminMails = $this->repository->findPairsByRoleId($roleAdmin->id, 'mail');
		$superadminMails = $this->repository->findPairsByRoleId($roleSuperadmin->id, 'mail');

		Assert::same([], $signedMails);
		Assert::same([
				3 => 'user1@domain.com',
				4 => 'user2@domain.com',
				5 => 'user3@domain.com',
				6 => 'user4@domain.com',
				7 => 'user5@domain.com',
				8 => 'user6@domain.com',
		], $usersMails);
		Assert::same([
				9 => 'dealer1@domain.com',
				10 => 'dealer2@domain.com',
				11 => 'dealer3@domain.com',
		], $dealersMails);
		Assert::same([
				1 => 'admin',
				12 => 'admin1@domain.com',
				13 => 'admin2@domain.com',
		], $adminMails);
		Assert::same([
				2 => 'superadmin',
		], $superadminMails);
	}

	public function testDelete()
	{
		Assert::exception(function () {
			$user = new User();
			$this->repository->delete($user);
		}, RepositoryException::class);
	}

}

$test = new UserRepositoryTest($container);
$test->run();
