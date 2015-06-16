<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="`group`")
 *
 * @property string $name
 * @property ArrayCollection $users
 */
class Group extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=50) */
	protected $name;

	/** @ORM\ManyToMany(targetEntity="User", mappedBy="groups") */
	protected $users;

	public function __construct($name = NULL)
	{
		if ($name) {
			$this->name = $name;
		}
		$this->users = new ArrayCollection();
		parent::__construct();
	}

    public function addUser(User $user)
    {
		if (!$this->users->contains($user)) {
			$this->users->add($user);
		}
		return $this;
    }

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

}
