<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\User;
use LogicException;

trait UserFacadeDelete
{

	/**
	 * Delete user by id
	 * @param int $id User ID
	 * @return bool
	 */
	public function deleteById($id)
	{
		$user = $this->userRepo->find($id);
		return $this->delete($user);
	}

	/**
	 * Delete user or throw exception
	 * @param User $user
	 * @return User
	 * @throws CantDeleteUserException
	 */
	public function delete(User $user)
	{
		if ($this->isDeletable($user)) {
			$this->clearPermissions($user);
			$this->em->remove($user);
			$this->em->flush();
			return $user;
		}
//		throw new CantDeleteUserException('You\'re only one admin');
	}

	public function isDeletable(User $user)
	{
		return TRUE;
	}

}

class CantDeleteUserException extends LogicException
{

}
