<?php

namespace Test\Examples\Model\Entity\Asociation\ManyToManySelfReferencing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 *
 * @property string $mail
 * @property ArrayCollection $friendsWithMe
 * @property ArrayCollection $myFriends
 */
class User extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=false, unique=true) */
	protected $mail;

	/**
	 * @ORM\ManyToMany(targetEntity="User", mappedBy="myFriends")
	 * */
	protected $friendsWithMe;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="friendsWithMe")
	 * @ORM\JoinTable(name="friends",
	 *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="friend_user_id", referencedColumnName="id")}
	 *      )
	 * */
	protected $myFriends;

	public function __construct()
	{
		parent::__construct();
		$this->friendsWithMe = new ArrayCollection();
		$this->myFriends = new ArrayCollection();
	}

}
