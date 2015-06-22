<?php

namespace App\Model\Facade;

use App\Extensions\Settings\Model\Service\ModuleService;
use App\Model\Entity\Role;
use Kdyby\Doctrine\EntityDao;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class RoleFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var ModuleService @inject */
	public $moduleService;

	/** @var EntityDao */
	private $roleDao;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->roleDao = $this->em->getDao(Role::getClassName());
	}

	/**
	 * Create role if is not exists.
	 * @param string $name
	 * @return Role|null
	 */
	public function create($name)
	{
		if ($this->isUnique($name)) {
			$entity = new Role($name);
			$this->em->persist($entity);
			$this->em->flush();
			return $entity;
		}
		return NULL;
	}

	public function findByName($name)
	{
		return $this->roleDao->findOneBy(['name' => $name]);
	}

	public function findLowerRoles(array $roles, $includeMax = FALSE)
	{
		$allRoles = $this->roleDao->findPairs('name'); // expect roles by priority (first is the lowest)
		$lowerRoles = [];
		$maxRole = Role::getMaxRole($roles);
		if (in_array($maxRole->name, $allRoles)) {
			foreach ($allRoles as $id => $dbRole) {
				if ($maxRole->name === $dbRole) {
					if ($includeMax) {
						$lowerRoles[$id] = $dbRole;
					}
					break;
				}
				$lowerRoles[$id] = $dbRole;
			}
		}
		return $lowerRoles;
	}

	public function isUnique($name)
	{
		return $this->findByName($name) === NULL;
	}

}
