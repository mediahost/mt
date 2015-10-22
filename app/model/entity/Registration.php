<?php

namespace App\Model\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Security\Passwords;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\RegistrationRepository")
 *
 * @property string $mail
 * @property string $name
 * @property-write string $password
 * @property string $hash
 * @property Role $role
 * @property bool $wantBeDealer
 * @property Address $billingAddress
 * @property string $facebookId
 * @property string $facebookAccessToken
 * @property string $twitterId
 * @property-read string $twitterAccessToken
 * @property-read string $verificationToken
 * @property DateTime $verificationExpiration
 */
class Registration extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string") */
	protected $mail;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $hash;

	/**
	 * @ORM\ManyToOne(targetEntity="Role", fetch="EAGER")
	 * @ORM\JoinColumn(name="role_id", referencedColumnName="id", nullable=true)
	 */
	protected $role;

	/** @ORM\Column(type="boolean") */
	protected $wantBeDealer = FALSE;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist"}) */
	protected $billingAddress;

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $facebookId;

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $facebookAccessToken;

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $twitterId;

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $twitterAccessToken;

	/** @ORM\Column(type="string", length=256) */
	protected $verificationToken;

	/** @ORM\Column(type="datetime") */
	protected $verificationExpiration;

	public function setPassword($password, array $options = NULL)
	{
		$this->hash = Passwords::hash($password, $options);
		return $this;
	}

	public function setVerification($token, $expiration)
	{
		$this->verificationToken = $token;
		$this->verificationExpiration = $expiration;
		return $this;
	}

}
