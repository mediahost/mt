<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property string $accessToken
 * @property string $name
 * @property string $screenName
 * @property string $url
 * @property string $location
 * @property string $description
 * @property string $statusesCount
 * @property string $lang
 */
class Twitter extends OAuth
{

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $accessToken;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $name;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $screenName;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $url;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $location;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $description;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $statusesCount;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $lang;

	public function __construct($id = NULL)
	{
		if ($id) {
			$this->id = $id;
		}
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->screenName;
	}

}
