<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Role;

/**
 * @property-read array $roles
 * @property-read array $rolesKeys
 * @property Role $maxRole
 * @property Role $requiredRole
 * @method self setRequiredRole(Role $role)
 */
trait UserRoles
{

	/** @ORM\ManyToMany(targetEntity="Role", fetch="LAZY", cascade={"persist"}) */
	private $roles;

	/**
	 * @ORM\ManyToOne(targetEntity="Role", fetch="LAZY")
	 * @ORM\JoinColumn(name="required_role_id", referencedColumnName="id", nullable=true)
	 */
	protected $requiredRole;

	public function addRole(Role $role)
	{
		if (!$this->roles->contains($role)) {
			$this->roles->add($role);
		}
		return $this;
	}

	public function addRoles(array $roles)
	{
		foreach ($roles as $role) {
			$this->addRole($role);
		}
		return $this;
	}

	public function clearRoles()
	{
		$this->roles->clear();
		return $this;
	}

	public function removeRole(Role $role)
	{
		$this->roles->removeElement($role);
		return $this;
	}

	public function getRolesKeys()
	{
		$array = [];
		foreach ($this->roles as $role) {
			$array[] = $role->id;
		}
		return $array;
	}

	public function isForAllShops()
	{
		return in_array(Role::SUPERADMIN, $this->getRoles());
	}

	/** @return array with roleID => roleName */
	public function getRoles()
	{
		$array = [];
		foreach ($this->roles as $role) {
			if ($role->id) {
				$array[$role->id] = $role->name;
			} else {
				$array[] = $role->name;
			}
		}
		return $array;
	}

	public function getMaxRole()
	{
		return Role::getMaxRole($this->roles->toArray());
	}

}