<?php

namespace App\Model\Entity\Newsletter;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="newsletter_message")
 */
class Message extends BaseEntity
{

	const TYPE_USER = 0;
	const TYPE_DEALER = 1;
	const TYPE_GROUP = 2;
	const STATUS_PAUSED = 0;
	const STATUS_RUNNING = 1;
	const STATUS_SENT = 2;

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=255) */
	protected $subject;

	/** @ORM\Column(type="text") */
	protected $content;

	/** @ORM\Column(type="string", length=2, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="smallint", length=32, options={"unsigned"=true}) */
	protected $type;

	/** @ORM\Column(type="smallint", length=32, options={"unsigned"=true}) */
	protected $status;

	/** @ORM\Column(type="datetime") */
	protected $created;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\Group")
	 * @ORM\JoinColumn(nullable=true)
	 */
	protected $group;

	/** @ORM\Column(type="boolean") */
	protected $unsubscribable = TRUE;

	/** @ORM\OneToMany(targetEntity="Status", mappedBy="$message") */
	protected $statuses;

	public function __construct()
	{
		$this->statuses = new ArrayCollection();
	}

	public function setLocale($locale)
	{
		if (empty($locale)) {
			$this->locale = NULL;
		} else {
			$this->locale = $locale;
		}
	}

	public function __toString()
	{
		return $this->subject;
	}

}
