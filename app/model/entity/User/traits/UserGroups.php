<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Group;

/**
 * @property array $groups
 */
trait UserGroups
{

	/** @ORM\ManyToMany(targetEntity="Group", inversedBy="users") */
	protected $groups;

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
	
	public function getGroup()
	{
		return $this->groups->count() ? $this->groups->first() : NULL;
	}

}