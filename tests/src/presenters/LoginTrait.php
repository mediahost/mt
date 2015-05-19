<?php

namespace Test\Presenters;

use App\Model\Facade\UserFacade;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Tester\Assert;

trait LoginTrait
{

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var User */
	protected $identity;

	protected function initIdentity()
	{
		$this->identity = $this->getContainer()->getByType(User::class);
	}

	protected function login(IIdentity $user)
	{
		Assert::false($this->identity->loggedIn);
		$this->identity->login($user);
		Assert::true($this->identity->loggedIn);
	}

	protected function loginAdmin()
	{
		$this->login($this->userFacade->findByMail('admin'));
	}

	protected function loginSuperadmin()
	{
		$this->login($this->userFacade->findByMail('superadmin'));
	}

	protected function loginUser()
	{
		$this->login($this->userFacade->findByMail('user'));
	}

	protected function loginDealer()
	{
		$this->login($this->userFacade->findByMail('dealer'));
	}

	protected function loginSigned()
	{
		$this->login($this->userFacade->findByMail('signed'));
	}

	protected function logout()
	{
		$this->identity->logout();
	}

}
