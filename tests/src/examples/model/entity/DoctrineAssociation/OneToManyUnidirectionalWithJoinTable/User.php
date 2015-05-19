<?php

namespace Test\Examples\Model\Entity\Asociation\OneToManyUnidirectionalWithJoinTable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 *
 * @property string $mail
 * @property Phonenumber $address
 */
class User extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=false, unique=true) */
	protected $mail;

	/**
	 * @ORM\ManyToMany(targetEntity="Phonenumber")
	 * @ORM\JoinTable(name="users_phonenumbers",
	 *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="phonenumber_id", referencedColumnName="id", unique=true)}
	 * )
	 */
	protected $phonenumbers;

	public function __construct()
	{
		parent::__construct();
		$this->phonenumbers = new ArrayCollection();
	}

}
