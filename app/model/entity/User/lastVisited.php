<?php

namespace App\Model\Entity;

use App\Model\Entity\Traits\IUserSocials;
use App\Model\Entity\Traits\UserGroups;
use App\Model\Entity\Traits\UserPassword;
use App\Model\Entity\Traits\UserRoles;
use App\Model\Entity\Traits\UserSocials;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Security\IIdentity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\UserRepository")
 *
 * @property string $mail
 * @property string $locale
 * @property string $currency
 * @method User setMail(string $mail)
 */
class LastVisited extends BaseEntity
{

	//use Identifier;

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="lastVisited")
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
	 */
	protected $user;
	
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Product")
	 * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
	 */
	protected $product;
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $visited;

	public function __toString()
	{
		return (string) "ToDo";
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'mail' => $this->mail,
			'role' => $this->roles->toArray(),
		];
	}

}
