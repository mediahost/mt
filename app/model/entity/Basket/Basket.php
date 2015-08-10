<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\BasketRepository")
 *
 * @property BasketItem[] $items
 */
class Basket extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	public function __construct(User $user = NULL)
	{
		if ($user) {
			$this->setUser($user);
		}
		parent::__construct();
	}

	/** @ORM\OneToOne(targetEntity="User", inversedBy="basket") */
	protected $user;

	/** @ORM\OneToMany(targetEntity="BasketItem", mappedBy="basket") */
	protected $items;
	
	public function setUser(User $user)
	{
		$this->user = $user;
		$user->basket = $this;
		return $this;
	}

}
