<?php

namespace Test\Examples\Model\Entity\Asociation\ManyToManyBidirectional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 *
 * @property string $mail
 * @property ArrayCollection $groups
 */
class User extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=false, unique=true) */
	protected $mail;

	/**
	 * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
	 * @ORM\JoinTable(name="users_groups")
	 */
	protected $groups;

	public function __construct()
	{
		parent::__construct();
		$this->groups = new ArrayCollection();
	}
	
    public function addGroup(Group $group)
    {
        $group->addUser($this); // synchronously updating inverse side
        $this->groups[] = $group;
    }
	

}
