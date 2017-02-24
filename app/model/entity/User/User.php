<?php

namespace App\Model\Entity;

use App\Model\Entity\Newsletter\Subscriber;
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
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="user_unique", columns={"mail", "shop"})})
 * @ORM\Entity(repositoryClass="App\Model\Repository\UserRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\UserListener"})
 *
 * @property string $mail
 * @property Group $group
 * @property string $locale
 * @property string $currency
 * @property Basket $basket
 * @property Address $billingAddress
 * @property Address $shippingAddress
 * @property Subscriber $subscriber
 * @property bool $wantBeDealer
 * @property int $bonusCount
 * @property Shop $shop
 * @method User setMail(string $mail)
 * @method User setLocale(string $locale)
 * @method User setCurrency(string $code)
 * @method User setShop(Shop $shop)
 */
class User extends BaseEntity implements IIdentity, IUserSocials
{

	// TODO: UPDATE `user` SET `shop_id` = '1';

	use Identifier;
	use UserRoles;
	use UserGroups;
	use UserPassword;
	use UserSocials;

	/** @ORM\Column(type="string", nullable=false) */
	protected $mail;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $currency;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $sidebarClosed;

	/** @ORM\OneToOne(targetEntity="Basket", mappedBy="user", fetch="LAZY") */
	protected $basket;

	/** @ORM\OneToMany(targetEntity="Order", mappedBy="user", fetch="EXTRA_LAZY") */
	protected $orders;

	/** @ORM\OneToOne(targetEntity="Address") */
	protected $shippingAddress;

	/** @ORM\OneToOne(targetEntity="Address") */
	protected $billingAddress;

	/** @ORM\OneToMany(targetEntity="Visit", mappedBy="user", fetch="EXTRA_LAZY") */
	protected $visits;

	/** @ORM\OneToOne(targetEntity="App\Model\Entity\Newsletter\Subscriber", mappedBy="user", fetch="EXTRA_LAZY", cascade={"persist"}) */
	protected $subscriber;

	/** @ORM\Column(type="boolean") */
	protected $wantBeDealer = FALSE;

	/** @ORM\Column(type="integer") */
	protected $bonusCount = 0;

	/** @ORM\ManyToOne(targetEntity="Shop") */
	public $shop;

	public function __construct($mail = NULL)
	{
		$this->roles = new ArrayCollection;
		$this->groups = new ArrayCollection();
		$this->orders = new ArrayCollection();
		$this->visits = new ArrayCollection();

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

	public function import(User $user, Basket $basket = NULL)
	{
		if (!$this->basket) {
			$this->basket = new Basket($this);
		}
		if ($user->basket) {
			$this->basket->import($user->basket, TRUE);
		} else if ($basket) {
			$this->basket->import($basket, TRUE);
		}
		return $this;
	}

	public function setSubscriber($subscriber)
	{
		$this->subscriber = $subscriber;
		$subscriber->user = $this;
		return $this;
	}

	public function removeSubscriber()
	{
		$this->subscriber = NULL;
		return $this;
	}
	
	public function getShippingAddress($realShipping = FALSE)
	{
		if ($realShipping) {
			if ($this->shippingAddress && $this->shippingAddress->isComplete()) {
				return $this->shippingAddress;
			} else {
				return $this->billingAddress;
			}
		} else {
			return $this->shippingAddress;
		}
	}

}
