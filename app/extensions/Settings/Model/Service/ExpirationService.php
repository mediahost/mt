<?php

namespace App\Extensions\Settings\Model\Service;

/**
 * @property-read string $recovery Recovery password token time
 * @property-read string $verification Verification token time
 * @property-read string $registration Registration token time
 * @property-read string $remember remember session time
 * @property-read string $notRemember Not remember session time
 */
class ExpirationService extends BaseService
{

	public function getRecovery()
	{
		return $this->defaultStorage->expiration->recovery;
	}

	public function getVerification()
	{
		return $this->defaultStorage->expiration->verification;
	}

	public function getRegistration()
	{
		return $this->defaultStorage->expiration->registration;
	}

	public function getRemember()
	{
		return $this->defaultStorage->expiration->remember;
	}

	public function getNotRemember()
	{
		return $this->defaultStorage->expiration->notRemember;
	}

}
