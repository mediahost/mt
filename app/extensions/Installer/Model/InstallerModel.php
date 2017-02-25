<?php

namespace App\Extensions\Installer\Model;

use App\Helpers;
use App\Model\Entity\OrderState;
use App\Model\Entity\OrderStateType;
use App\Model\Entity\Page;
use App\Model\Entity\Payment;
use App\Model\Entity\Role;
use App\Model\Entity\Shipping;
use App\Model\Entity\Sign;
use App\Model\Entity\Unit;
use App\Model\Entity\Vat;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Nette\InvalidArgumentException;
use Nette\Object;
use Nette\Utils\FileSystem;

class InstallerModel extends Object
{

	const ADMINER_FILENAME = '/adminer/database-log.sql';

	// <editor-fold desc="injects">

	/** @var EntityManager @inject */
	public $em;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	// </editor-fold>
	// <editor-fold desc="installers">

	/**
	 * Create all nested roles
	 * @return boolean
	 */
	public function installRoles(array $roles)
	{
		foreach ($roles as $roleName) {
			$this->roleFacade->create($roleName);
		}
		return TRUE;
	}

	/**
	 * Create all nested roles
	 * @return boolean
	 */
	public function installUnits(array $units)
	{
		$unitRepo = $this->em->getRepository(Unit::getClassName());
		foreach ($units as $unitName) {
			$finded = $unitRepo->findOneByName($unitName);
			if (!$finded) {
				$unit = new Unit($unitName);
				$unit->mergeNewTranslations();
				$this->em->persist($unit);
			}
		}
		$this->em->flush();
		return TRUE;
	}

	/**
	 * Create default users
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function installUsers(array $users)
	{
		foreach ($users as $initUserMail => $initUserData) {
			if (!is_array($initUserData) || !array_key_exists(0, $initUserData) || !array_key_exists(1, $initUserData)) {
				throw new InvalidArgumentException('Invalid users array. Must be [user_mail => [password, role]].');
			}
			$pass = $initUserData[0];
			$role = $initUserData[1];
			$roleRepo = $this->em->getRepository(Role::getClassName());
			$roleEntity = $roleRepo->findOneByName($role);
			if (!$roleEntity) {
				throw new InvalidArgumentException('Invalid name of role. Check if exists role with name \'' . $role . '\'.');
			}
			$this->userFacade->create($initUserMail, $pass, $roleEntity);
		}
		return TRUE;
	}

	/**
	 * Create default signs
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function installSigns(array $signs)
	{
		$this->allowChangeEntityId(Sign::getClassName());
		foreach ($signs as $signName => $signId) {
			$signRepo = $this->em->getRepository(Sign::getClassName());
			$signEntity = $signRepo->find($signId);
			if (!$signEntity) {
				$signEntity = new Sign($signName, NULL, $signId);
				$signRepo->save($signEntity);
			}
		}
		return TRUE;
	}

	/**
	 * Create default pages
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function installPages($maxId)
	{
		for ($id = 1; $id <= $maxId; $id++) {
			$pageRepo = $this->em->getRepository(Page::getClassName());
			$pageEntity = $pageRepo->find($id);
			if (!$pageEntity) {
				$pageEntity = new Page(NULL, $id);
				$pageEntity->name = 'example page ' . $id;
				$this->em->persist($pageEntity);
			}
		}
		$this->em->flush();
		return TRUE;
	}

	/**
	 * Create default order states
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function installOrders(array $states, array $types)
	{
		$typeRepo = $this->em->getRepository(OrderStateType::getClassName());
		$stateRepo = $this->em->getRepository(OrderState::getClassName());
		foreach ($types as $name => $locking) {
			$type = $typeRepo->findOneByName($name);
			if (!$type) {
				$type = new OrderStateType();
				$type->name = $name;
			}
			$type->locking = $locking;
			$typeRepo->save($type);
		}
		foreach ($states as $name => $typeName) {
			$state = $stateRepo->findOneByName($name);
			$type = $typeRepo->findOneByName($typeName);
			if (!$state) {
				$state = new OrderState();
				$state->name = $name;
			}
			$state->type = $type;
			$stateRepo->save($state);
		}
		return TRUE;
	}

	/**
	 * Clear folders in temp dir
	 * @param string $tempDir
	 * @return boolean
	 */
	public function clearTempDir($tempDir)
	{
		$folders = [
			'proxies',
		];
		foreach ($folders as $folder) {
			$path = Helpers::getPath($tempDir, $folder);
			FileSystem::delete($path);
		}
		return TRUE;
	}

	/**
	 * Update database
	 * @return boolean
	 */
	public function installDoctrine()
	{
		$tool = new SchemaTool($this->em);
		$classes = $this->em->getMetadataFactory()->getAllMetadata();
		$tool->updateSchema($classes); // php index.php orm:schema-tool:update --force
		return TRUE;
	}

	/**
	 * Set database as writable
	 * @param type $wwwDir
	 * @param string $file
	 * @return boolean
	 * @deprecated It FAILS on server (chmod has insufficient permissions), its required special settings for FTP deployment
	 */
	public function installAdminer($wwwDir, $file = NULL)
	{
		if (!$file) {
			$file = $wwwDir . self::ADMINER_FILENAME;
		}
		if (is_dir($wwwDir)) {
			if (!file_exists($file)) {
				$handle = fopen($file, "w");
				fclose($handle);
			}
			@chmod($file, 0777);
		}
		return TRUE;
	}

	/**
	 * Install or update composer
	 * NON TESTED - only for localhost use
	 * @param string $appDir
	 * @param string $print
	 * @return boolean
	 * @deprecated using shell_exec
	 */
	public function installComposer($appDir, &$print = NULL)
	{
		$oldcwd = getcwd();
		chdir($oldcwd . "/.."); // TODO: remove - by using "-d './../'" as composer param (used in deployment.config.php)
		$print = @shell_exec('composer instal'); // TODO: use system as FTP-Deployment by DG (with no chdir)
		chdir($oldcwd);
		return TRUE;
	}

	// </editor-fold>

	private function allowChangeEntityId($className)
	{
		$metadata = $this->em->getClassMetaData($className);
		$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
		$metadata->setIdGenerator(new AssignedGenerator());
	}

}
