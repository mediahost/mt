<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\Registration;
use Nette\Utils\DateTime;

trait UserFacadeFinders
{

	public function findByMail($mail)
	{
		return $this->userRepo->findOneBy(['mail' => $mail]);
	}

	public function findByFacebookId($id)
	{
		return $this->userRepo->findOneBy(['facebook.id' => $id]);
	}

	public function findByClientId($id)
	{
		return $this->userRepo->findOneBy(['clientId' => $id]);
	}

	public function findByTwitterId($id)
	{
		return $this->userRepo->findOneBy(['twitter.id' => $id]);
	}

	/**
	 * Find only valid entities
	 * Expired sign up request is deleted
	 * @param string $token
	 * @return Registration
	 */
	public function findByVerificationToken($token)
	{
		$registration = $this->registrationRepo->findOneBy(['verificationToken' => $token]);

		if ($registration) {
			if ($registration->verificationExpiration > new DateTime()) {
				return $registration;
			} else {
				$this->registrationRepo->delete($registration);
			}
		}

		return NULL;
	}

	public function findByRecoveryToken($token)
	{
		if (!empty($token)) {
			$user = $this->userRepo->findOneBy([
					'recoveryToken' => $token
			]);

			if ($user) {
				if ($user->recoveryExpiration > new DateTime()) {
					return $user;
				} else {
					$user->removeRecovery();
					$this->userRepo->save($user);
				}
			}
		}

		return NULL;
	}

}
