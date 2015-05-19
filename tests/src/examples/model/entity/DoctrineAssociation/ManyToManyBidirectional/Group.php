<?php

namespace Test\Examples\Model\Entity\Asociation\ManyToManyBidirectional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Inverzed
 * @ORM\Entity
 * @ORM\Table(name="`group`")
 * 
 * @property string $name
 * @property ArrayCollection $users
 */
class Group extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $name;

	/**
	 * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
	 */
	protected $users;

	public function __construct()
	{
		parent::__construct();
		$this->users = new ArrayCollection();
	}

    public function addUser(User $user)
    {
        $this->users[] = $user;
    }

}
