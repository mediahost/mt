<?php

namespace App\Model\Facade\Traits;

use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use InvalidArgumentException;

trait UserFacadeSetters
{

	/**
	 * Add role as Role entity, string or array of entites to user.
	 * @param User $user
	 * @param Role|string|array $role
	 * @return User
	 * @throws InvalidArgumentException
	 */
	public function addRole(User $user, $role)
	{
		if (is_string($role) || $role instanceof Role) {
			return $user->addRole($this->roleDao->findOneBy(['name' => (string) $role]));
		} elseif (is_array($role)) {
			return $user->addRoles($this->roleDao->findBy(['name' => $role]));
		} else {
			throw new InvalidArgumentException;
		}
	}

	/**
	 * Append settings to user
	 * @param int $userId
	 * @param PageConfigSettings $configSettings
	 * @return self
	 */
	public function appendSettings($userId, PageConfigSettings $configSettings = NULL)
	{
		$user = $this->userRepo->find($userId);
		if ($user && $configSettings) {
			if (!$user->pageConfigSettings instanceof PageConfigSettings) {
				$user->pageConfigSettings = new PageConfigSettings;
			}
			$user->pageConfigSettings->append($configSettings);
			$this->em->persist($user->pageConfigSettings);
		}

		$this->em->flush();
		return $this;
	}

}
