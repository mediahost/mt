<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\User;
use Nette\Utils\Random;

trait UserFacadeRecovery
{

	/**
	 * Sets recovery token and expiration datetime to User.
	 * @param User $user
	 * @return self
	 */
	public function setRecovery(User &$user)
	{
		$user->setRecovery(Random::generate(32), 'now + ' . $this->settings->recovery);
		return $this;
	}

	/**
	 * @param User $user
	 * @param string $password
	 * @return self
	 */
	public function recoveryPassword(User &$user, $password)
	{
		$user->password = $password;
		$user->removeRecovery();
		return $this;
	}

}
