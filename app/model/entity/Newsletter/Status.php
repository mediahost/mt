<?php

namespace App\Model\Entity\Newsletter;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="newsletter_status",  uniqueConstraints={@ORM\UniqueConstraint(name="status_unique", columns={"message_id", "email"})})
 * 
 * @property string $email
 * @property Message $message
 * @property int $status
 * @property DateTime $sent
 * @property Subscriber $subscriber
 */
class Status extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=255) */
	protected $email;

	/** @ORM\ManyToOne(targetEntity="Message") */
	protected $message;

	/** @ORM\Column(type="smallint", length=32, options={"unsigned"=true}) */
	protected $status;

	/** @ORM\Column(type="datetime", nullable=true) */
	protected $sent;

	/**
	 * @ORM\ManyToOne(targetEntity="Subscriber", fetch="EAGER")
	 * @ORM\JoinColumn(nullable=true)
	 */
	protected $subscriber;

}
