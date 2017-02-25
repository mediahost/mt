<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\Registration;
use Nette\Utils\DateTime;

trait UserFacadeFinders
{

	public function findByMail($mail)
	{
		return $this->findOneBy(['mail' => $mail]);
	}

	public function findByFacebookId($id)
	{
		return $this->findOneBy(['facebook.id' => $id]);
	}

	public function findByClientId($id)
	{
		return $this->findOneBy(['clientId' => $id]);
	}

	public function findByTwitterId($id)
	{
		return $this->findOneBy(['twitter.id' => $id]);
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
				'shop' => $this->shopFacade->getShopVariant()->shop,
				'recoveryToken' => $token,
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

	private function findOneBy(array $conditions)
	{
		$finded = $this->userRepo->findOneBy($conditions);
		if ($finded && $finded->shop->id !== $this->shopFacade->getShopVariant()->shop->id && !$finded->isForAllShops()) {
			$finded = NULL;
		}
		return $finded;
	}

}
