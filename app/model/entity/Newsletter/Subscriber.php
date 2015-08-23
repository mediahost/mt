<?php

namespace App\Model\Entity\Newsletter;

use App\Model\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="newsletter_subscriber",  uniqueConstraints={@ORM\UniqueConstraint(name="subscriber_unique", columns={"mail", "type"})})
 * 
 * @property string $mail
 * @property int $type
 * @property string $locale
 * @property string $token
 * @property User $user
 * @property string $ip
 * @property DateTime $subscribed
 */
class Subscriber extends BaseEntity
{

	const TYPE_USER = 0;
	const TYPE_DEALER = 1;

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=255) */
	protected $mail;

	/** @ORM\Column(type="smallint", length=32, options={"unsigned"=true}) */
	protected $type;

	/** @ORM\Column(type="string", length=2) */
	protected $locale;

	/** @ORM\Column(type="string", length=8, unique=true, nullable=true) */
	protected $token;

	/** @ORM\OneToOne(targetEntity="App\Model\Entity\User", mappedBy="subscriber") */
	protected $user;

	/** @ORM\Column(type="string", length=39) */
	protected $ip;

	/** @ORM\Column(type="datetime") */
	protected $subscribed;

}
