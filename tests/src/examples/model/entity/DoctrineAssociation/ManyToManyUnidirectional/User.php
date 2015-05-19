<?php

namespace Test\Examples\Model\Entity\Asociation\ManyToManyUnidirectional;

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
	 * @ORM\ManyToMany(targetEntity="Group")
	 * @ORM\JoinTable(name="users_groups",
	 *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
	 * )
	 */
	protected $groups;

	public function __construct()
	{
		parent::__construct();
		$this->groups = new ArrayCollection();
	}

}
