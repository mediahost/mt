<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\Facebook;
use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use Nette\Utils\DateTime;
use Nette\Utils\Random;

trait UserFacadeCreates
{

	/**
	 * @param string $mail
	 * @param string $password
	 * @param Role $role
	 * @return User
	 */
	public function create($mail, $password, Role $role)
	{
		if ($this->isUnique($mail)) {
			$user = new User();
			$user->setMail($mail)
					->setPassword($password)
					->addRole($role);

			return $this->userRepo->save($user);
		}
		return NULL;
	}

	/**
	 * Create user from registration and delete registration entity
	 * @param Registration $registration
	 * @param Role $role
	 * @return User
	 */
	public function createFromRegistration(Registration $registration, Role $role)
	{
		$user = new User($registration->mail);
		$user->setHash($registration->hash)
				->addRole($role)
				->setRequiredRole($registration->role);

		if ($registration->facebookId) {
			$user->facebook = new Facebook($registration->facebookId);
			$user->facebook->setAccessToken($registration->facebookAccessToken);
		}
		if ($registration->twitterId) {
			$user->twitter = new Twitter($registration->twitterId);
			$user->twitter->setAccessToken($registration->twitterAccessToken);
		}

		$this->registrationRepo->delete($registration);

		return $this->userRepo->save($user);
	}

	/**
	 * Create registration
	 * @param User $user
	 * @return Registration
	 */
	public function createRegistration(User $user)
	{
		$this->deleteRegistrations($user->mail);

		$registration = new Registration();
		$registration->setMail($user->mail)
				->setHash($user->hash)
				->setRole($this->roleDao->find($user->requiredRole->id));

		if ($user->facebook) {
			$registration->setFacebookId($user->facebook->id)
					->setFacebookAccessToken($user->facebook->accessToken);
		}

		if ($user->twitter) {
			$registration->setTwitterId($user->twitter->id)
					->setTwitterAccessToken($user->twitter->accessToken);
		}

		$registration->verificationToken = Random::generate(32);
		$registration->verificationExpiration = new DateTime('now + ' . $this->expirationService->verification);

		$this->registrationRepo->save($registration);

		return $registration;
	}

	/**
	 * Clear registrations by mail
	 * @param string $mail
	 * @return mixed
	 */
	private function deleteRegistrations($mail)
	{
		return $this->registrationRepo->deleteByMail($mail);
	}

}
