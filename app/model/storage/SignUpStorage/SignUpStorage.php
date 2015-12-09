<?php

namespace App\Model\Storage;

use App\Model\Entity\User;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Object;

/**
 * Description of RegistrationStorage
 *
 * @property User $user
 * @property string $role
 * @property-set bool $verification
 * @property-read string $redirectRole
 */
class SignUpStorage extends Object
{

	/** @var Session */
	public $session;

	/** @var SessionSection */
	public $section;

	public function __construct(Session $session)
	{
		$this->section = $session->getSection('registration');
		$this->session = $session;

		$this->section->warnOnUndefined = TRUE;

		$this->initSession();
	}

	private function initSession($force = FALSE)
	{
		$defaults = [
				'verification' => FALSE,
				'user'         => new User(),
				'role'         => NULL,
		];
		foreach ($defaults as $property => $value) {
			if ($force || !isset($this->section->{$property})) {
				$this->section->{$property} = $value;
			}
		}
	}

	public function setUser(User $user)
	{
		$this->section->user = $user;
		return $this;
	}

	public function getUser()
	{
		return $this->section->user;
	}

	public function setVerification($bool)
	{
		$this->section->verification = $bool;
		return $this;
	}

	public function isVerified()
	{
		return $this->section->verification;
	}

	public function wipe()
	{
		$this->initSession(TRUE);
	}

	public function remove()
	{
		$this->section->remove();
	}

}
