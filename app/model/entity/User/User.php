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
class User extends BaseEntity implements IIdentity, IUserSocials
{

	use Identifier;
	use UserRoles;
	use UserGroups;
	use UserPassword;
	use UserSocials;

	/**
	 * @ORM\Column(type="string", nullable=false, unique=true)
	 */
	protected $mail;

	/**
	 * @ORM\Column(type="string", length=8, nullable=true)
	 */
	protected $locale;

	/**
	 * @ORM\Column(type="string", length=8, nullable=true)
	 */
	protected $currency;

	/**
	 * @ORM\Column(type="boolean", nullable=true)
	 */
	protected $sidebarClosed;

	/**
	 * @ORM\OneToMany(targetEntity="VisitedProduct", mappedBy="user", fetch="EXTRA_LAZY")
	 */
	protected $visitedProducts;

	public function __construct($mail = NULL)
	{
		$this->roles = new ArrayCollection;
		$this->groups = new ArrayCollection();
		$this->visitedProducts = new ArrayCollection();

		if ($mail) {
			$this->mail = $mail;
		}

		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->mail;
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'mail' => $this->mail,
			'role' => $this->roles->toArray(),
		];
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function import(User $user)
	{
		
	}

}
