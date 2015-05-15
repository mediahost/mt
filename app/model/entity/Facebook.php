<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property string $accessToken
 * @property string $mail
 * @property string $name
 * @property string $birthday
 * @property string $gender
 * @property string $hometown
 * @property string $link
 * @property string $location
 * @property string $locale
 * @property string $username
 */
class Facebook extends OAuth
{

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $accessToken;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $mail;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $name;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $birthday;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $gender;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $hometown;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $link;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $location;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $username;

	public function __construct($id = NULL)
	{
		if ($id) {
			$this->id = $id;
		}
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
