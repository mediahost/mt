<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Group;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Utils\Random;

/**
 * @property ArrayCollection $groups
 * @property string $clientId client ID for dealer API
 */
trait UserGroups
{

	/** @ORM\ManyToMany(targetEntity="Group", inversedBy="users") */
	protected $groups;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	private $clientId;

	public function setGroups(array $groups)
	{
		$removeIdles = function ($key, Group $group) use ($groups) {
			if (!in_array($group, $groups, TRUE)) {
				$this->removeGroup($group);
			}
			return TRUE;
		};
		$this->groups->forAll($removeIdles);
		foreach ($groups as $group) {
			$this->addGroup($group);
		}
		return $this;
	}

	public function addGroup(Group $group)
	{
		if (!$this->groups->contains($group)) {
			$this->groups->add($group);
			$group->addUser($this);
		}
		return $this;
	}

	public function removeGroup(Group $group)
	{
		return $this->groups->removeElement($group);
	}

	public function clearGroups($withBonus = FALSE)
	{
		if ($withBonus) {
			$this->groups->clear();
		} else {
			$this->clearGroupsByType(Group::TYPE_DEALER);
		}
		return $this;
	}

	public function clearGroupsByType($type = Group::TYPE_DEALER)
	{
		$removeDealer = function ($key, Group $group) use ($type) {
			if ($group->type === $type) {
				$this->removeGroup($group);
			}
			return TRUE;
		};
		$this->groups->forAll($removeDealer);
		return $this;
	}

	public function getGroup()
	{
		$group = $this->getDealerGroup();
		if (!$group) {
			$group = $this->getBonusGroup();
		}
		return $group;
	}

	public function getDealerGroup()
	{
		return $this->getFirstGroup(Group::TYPE_DEALER);
	}

	public function getBonusGroup()
	{
		return $this->getFirstGroup(Group::TYPE_BONUS);
	}

	protected function getFirstGroup($type = Group::TYPE_DEALER)
	{
		$firstGroup = NULL;
		$isGroupType = function ($key, Group $group) use ($type, &$firstGroup) {
			if ($group->type === $type) {
				$firstGroup = $group;
				return FALSE;
			}
			return TRUE;
		};
		if ($this->groups && $this->groups->count()) {
			$this->groups->forAll($isGroupType);
		}
		return $firstGroup;
	}

	public function isDealer()
	{
		return $this->isGroupType(Group::TYPE_DEALER);
	}

	public function isInBonus()
	{
		return $this->isGroupType(Group::TYPE_BONUS);
	}

	protected function isGroupType($type)
	{
		$isGroupType = function ($key, Group $group) use ($type) {
			return $group->type === $type;
		};
		return $this->groups->exists($isGroupType);
	}

	public function resetClientId()
	{
		$this->clientId = Random::generate(52);
		return $this;
	}

	public function getClientId()
	{
		return $this->clientId;
	}

}
