<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\User;
use App\Model\Facade\CantDeleteUserException;
use Kdyby\Doctrine\DBALException;

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
			try {
				if ($user->basket) {
					$this->em->remove($user->basket);
				}
				$this->em->remove($user);
				$this->em->flush();
				return $user;
			} catch (DBALException $ex) {
				
			}
		}
		throw new CantDeleteUserException('This user can\'t be deleted');
	}

	public function isDeletable(User $user)
	{
		return TRUE;
	}

}
