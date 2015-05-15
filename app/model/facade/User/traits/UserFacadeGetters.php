<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\Role;

trait UserFacadeGetters
{

	/**
	 * Get all users
	 * @return array
	 */
	public function getUsers()
	{
		return $this->userRepo->findPairs('mail');
	}

	/**
	 * Get all users in inserted role
	 * @param Role $role
	 * @return array
	 */
	public function getUserMailsInRole(Role $role)
	{
		return $this->userRepo->findPairsByRoleId($role->id, 'mail');
	}

}
