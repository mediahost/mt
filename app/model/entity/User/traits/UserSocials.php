<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Facebook;
use App\Model\Entity\Twitter;

interface IUserSocials
{

	const SOCIAL_CONNECTION_APP = 'app';
	const SOCIAL_CONNECTION_FACEBOOK = 'facebook';
	const SOCIAL_CONNECTION_TWITTER = 'twitter';
	const SOCIAL_CONNECTION_GOOGLE = 'google';
	const SOCIAL_CONNECTION_GITHUB = 'github';
	const SOCIAL_CONNECTION_LINKEDIN = 'linkedin';

}

/**
 * @property Facebook $facebook
 * @property Twitter $twitter
 * @property string $socialName
 * @property string $socialBirthday
 * @property int $connectionCount
 */
trait UserSocials
{

	/** @ORM\OneToOne(targetEntity="Facebook", fetch="LAZY", cascade={"persist", "remove"}) */
	protected $facebook;

	/** @ORM\OneToOne(targetEntity="Twitter", fetch="LAZY", cascade={"persist", "remove"}) */
	protected $twitter;

	public function clearFacebook()
	{
		$this->facebook = NULL;
		return $this;
	}

	public function clearTwitter()
	{
		$this->twitter = NULL;
		return $this;
	}

	public function getSocialName()
	{
		if ($this->facebook) { // prefer FB
			return $this->facebook->name;
		}
		if ($this->twitter) {
			return $this->twitter->name;
		}
		return NULL;
	}

	public function getSocialBirthday()
	{
		if ($this->facebook) {
			return $this->facebook->birthday;
		}
		return NULL;
	}

	public function hasSocialConnection($socialConnectionName)
	{
		switch ($socialConnectionName) {
			case IUserSocials::SOCIAL_CONNECTION_APP:
				return (bool) $this->hash;
			case IUserSocials::SOCIAL_CONNECTION_FACEBOOK:
				return (bool) ($this->facebook instanceof Facebook && $this->facebook->id);
			case IUserSocials::SOCIAL_CONNECTION_TWITTER:
				return (bool) ($this->twitter instanceof Twitter && $this->twitter->id);
			default:
				return FALSE;
		}
	}

	public function getConnectionCount()
	{
		$allConnections = [
				IUserSocials::SOCIAL_CONNECTION_APP,
				IUserSocials::SOCIAL_CONNECTION_FACEBOOK,
				IUserSocials::SOCIAL_CONNECTION_GITHUB,
				IUserSocials::SOCIAL_CONNECTION_GOOGLE,
				IUserSocials::SOCIAL_CONNECTION_LINKEDIN,
				IUserSocials::SOCIAL_CONNECTION_TWITTER,
		];
		$count = 0;
		foreach ($allConnections as $connection) {
			if ($this->hasSocialConnection($connection)) {
				$count++;
			}
		}
		return $count;
	}

}