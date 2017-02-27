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

	/** @var \App\Model\Facade\NewsletterFacade @inject */
	public $newsletterFacade;

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
				->addRole($role)
				->setLocale($this->translator->getDefaultLocale())
				->setCurrency($this->exchange->getDefault()->getCode())
				->setShop($this->shopFacade->getShopVariant()->shop);

			$this->em->persist($user);

			$subscriber = $this->newsletterFacade->findSubscriber($mail);

			if ($subscriber) {
				$user->subscriber = $subscriber;
			}

			return $this->em->flush();
		}
		return NULL;
	}

	public function createForSocial()
	{
		$user = new User();
		$user
			->setLocale($this->translator->getLocale())
			->setCurrency($this->exchange->getDefault()->getCode())
			->setShop($this->shopFacade->getShopVariant()->shop);
		$user->requiredRole = $this->roleDao->findOneByName(Role::USER);
		return $user;
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
			->setRequiredRole($registration->role)
			->setLocale($this->translator->getLocale())
			->setCurrency($this->exchange->getWeb()->getCode())
			->setWantBeDealer($registration->wantBeDealer)
			->setShop($this->shopFacade->getShopVariant()->shop);

		if ($registration->billingAddress) {
			$user->billingAddress = $registration->billingAddress;
		}

		$this->em->persist($user);

		if ($registration->facebookId) {
			$facebook = $this->facebookRepo->find($registration->facebookId);
			if ($facebook) {
				$user->facebook = $facebook;
			} else {
				$user->facebook = new Facebook($registration->facebookId);
			}
			$user->facebook->setAccessToken($registration->facebookAccessToken);
		}

		if ($registration->twitterId) {
			$twitter = $this->facebookRepo->find($registration->twitterId);
			if ($twitter) {
				$user->twitter = $twitter;
			} else {
				$user->twitter = new Twitter($registration->twitterId);
			}
			$user->twitter->setAccessToken($registration->twitterAccessToken);
		}

		$subscriber = $this->newsletterFacade->findSubscriber($registration->mail);

		if ($subscriber) {
			$user->subscriber = $subscriber;
		}

		$this->registrationRepo->delete($registration);

		$this->em->flush();
		return $user;
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
		$registration
			->setMail($user->mail)
			->setHash($user->hash)
			->setRole($this->roleDao->find($user->requiredRole->id))
			->setWantBeDealer($user->wantBeDealer);

		if ($user->billingAddress) {
			$registration->billingAddress = $user->billingAddress;
		}

		if ($user->facebook) {
			$registration->setFacebookId($user->facebook->id)
				->setFacebookAccessToken($user->facebook->accessToken);
		}

		if ($user->twitter) {
			$registration->setTwitterId($user->twitter->id)
				->setTwitterAccessToken($user->twitter->accessToken);
		}

		$registration->setVerification(Random::generate(32), new DateTime('now + ' . $this->settings->expiration->verification));

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
