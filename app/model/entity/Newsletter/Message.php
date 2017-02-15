<?php

namespace App\Model\Entity\Newsletter;

use App\Model\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Http\FileUpload;

/**
 * @ORM\Entity
 * @ORM\Table(name="newsletter_message")
 */
class Message extends BaseEntity
{

	const TYPE_USER = 0;
	const TYPE_DEALER = 1;
	const TYPE_GROUP = 2;
	const TYPE_SHOP = 3;
	const STATUS_PAUSED = 0;
	const STATUS_RUNNING = 1;
	const STATUS_SENT = 2;

	use Identifier;

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

	/**
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\Shop")
	 * @ORM\JoinColumn(nullable=true)
	 */
	protected $shop;

	/** @ORM\Column(type="boolean") */
	protected $unsubscribable = TRUE;

	/** @ORM\OneToMany(targetEntity="Status", mappedBy="message") */
	protected $statuses;

	/** 
	 * @ORM\ManyToMany(targetEntity="App\Model\Entity\File", cascade="all")
	 * @ORM\JoinTable(name="newsletter_file")
	 */
	protected $attachments;
	
	public function __construct()
	{
		$this->statuses = new ArrayCollection();
		$this->attachments = new ArrayCollection();
		parent::__construct();
	}

	public function setLocale($locale)
	{
		if (empty($locale)) {
			$this->locale = NULL;
		} else {
			$this->locale = $locale;
		}
	}

	public function addAttachment(FileUpload $file)
	{
		$image = new File($file);
		$image->requestedFilename = $file->getSanitizedName();
		$image->setFolder(File::FOLDER_ATTACHMENTS);
		
		$this->attachments->add($image);
		
		return $this;
	}

	public function __toString()
	{
		return (string)$this->subject;
	}

}
