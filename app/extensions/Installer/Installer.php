<?php

namespace App\Extensions;

use App\Extensions\Installer\Model\InstallerModel;
use App\Extensions\Settings\SettingsStorage;
use App\Helpers;
use App\Model\Entity\Unit;
use Nette\Object;
use Nette\Security\IAuthorizator;

class Installer extends Object
{

	// <editor-fold desc="constants & variables">

	const LOCK_FILE_CONTENT = '1';
	const LOCK_UNNAMED = '_UNNAMED_';
	const INSTALL_SUCCESS = TRUE;
	const INSTALL_LOCKED = FALSE;

	/** @var string */
	private $tempDir;

	/** @var string */
	private $wwwDir;

	/** @var string */
	private $appDir;

	/** @var string */
	private $installDir;

	/** @var bool */
	private $lock = TRUE;

	/** @var bool */
	private $installDoctrine = FALSE;

	/** @var bool */
	private $installAdminer = FALSE;

	/** @var bool */
	private $installComposer = FALSE;

	/** @var array */
	private $initUsers = [];

	/** @var array */
	private $messages = [];

	// </editor-fold>
	// <editor-fold desc="injects">

	/** @var InstallerModel @inject */
	public $model;

	/** @var IAuthorizator @inject */
	public $permissions;

	/** @var SettingsStorage @inject */
	public $settings;

	// </editor-fold>
	// <editor-fold desc="events">

	/** @var array */
	public $onSuccessInstall = [];

	/** @var array */
	public $onLockedInstall = [];

	// </editor-fold>
	// <editor-fold desc="setters">

	/**
	 * Set nested pathes
	 * @param string $tempDir
	 * @param string $wwwDir
	 * @param string $appDir
	 * @return self
	 */
	public function setPathes($appDir, $wwwDir, $tempDir, $installDir)
	{
		$this->tempDir = $tempDir;
		$this->wwwDir = $wwwDir;
		$this->appDir = $appDir;
		$this->installDir = $installDir;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setLock($value)
	{
		$this->lock = (bool) $value;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setInstallDoctrine($value)
	{
		$this->installDoctrine = (bool) $value;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setInstallAdminer($value)
	{
		$this->installAdminer = (bool) $value;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setInstallComposer($value)
	{
		$this->installComposer = (bool) $value;
		return $this;
	}

	/**
	 * @param array $value
	 * @return self
	 */
	public function setInitUsers(array $value)
	{
		$this->initUsers = $value;
		return $this;
	}

	// </editor-fold>
	// <editor-fold desc="getters">

	private function getRoles()
	{
		return $this->permissions->getRoles();
	}

	// </editor-fold>

	/**
	 * Install and return messages array
	 * @return array
	 */
	public function install()
	{
		$this->installComposer();
		$this->installAdminer();
		$this->installDb();
		return $this->messages;
	}

	// <editor-fold desc="subinstallers">

	/**
	 * Run Composer
	 */
	private function installComposer()
	{
		if ($this->installComposer) {
			$name = $this->getLockName(__METHOD__);
			if ($this->lock($name)) {
				$print = NULL;
				$this->model->installComposer($this->appDir, $print);
				$this->onSuccessInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_SUCCESS, $print];
			} else {
				$this->onLockedInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_LOCKED];
			}
		}
	}

	/**
	 * Install Adminer needs
	 */
	private function installAdminer()
	{
		if ($this->installAdminer) {
			$name = $this->getLockName(__METHOD__);
			if ($this->lock($name)) {
				$this->model->installAdminer($this->wwwDir);
				$this->onSuccessInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_SUCCESS];
			} else {
				$this->onLockedInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_LOCKED];
			}
		}
	}

	/**
	 * Install DB needs
	 * can create/update DB tables (install doctrine)
	 * set all nested thing (users, roles) to DB
	 */
	private function installDb()
	{
		$prefix = 'DB_';
		$this->installDoctrine($prefix);
		$this->installRoles($prefix);
		$this->installUsers($prefix);
		$this->installVats($prefix);
		$this->installUnits($prefix);
		$this->installSigns($prefix);
		$this->installPages($prefix);
		$this->installOrders($prefix);
	}

	private function installDoctrine($lockPrefix = NULL)
	{
		if ($this->installDoctrine) {
			$name = $lockPrefix . $this->getLockName(__METHOD__);
			if ($this->lock($name)) {
				$this->model->clearTempDir($this->tempDir);
				$this->model->installDoctrine();
				$this->onSuccessInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_SUCCESS];
			} else {
				$this->onLockedInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_LOCKED];
			}
		}
	}

	/**
	 * Instal roles
	 * @param string $lockPrefix
	 */
	private function installRoles($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installRoles($this->getRoles());
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal units
	 * @param string $lockPrefix
	 */
	private function installUnits($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installUnits(Unit::getAllNames());
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal vats
	 * @param string $lockPrefix
	 */
	private function installVats($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$vats = (array) $this->settings->modules->vats;
			$this->model->installVats($vats);
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal users
	 * @param string $lockPrefix
	 */
	private function installUsers($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installUsers($this->initUsers);
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal signs
	 * @param string $lockPrefix
	 */
	private function installSigns($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$signs = [];
			$signSettings = $this->settings->modules->signs;
			if ($signSettings->enabled) {
				$signs = (array) $signSettings->values;
			}
			$this->model->installSigns($signs);
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal pages
	 * @param string $lockPrefix
	 */
	private function installPages($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$maxPageId = 1;
			$moduleSettings = $this->settings->modules;
			if ($moduleSettings->buyout->enabled) {
				$maxPageId = $moduleSettings->buyout->pageId > $maxPageId ? $moduleSettings->buyout->pageId : $maxPageId;
			}
			if ($moduleSettings->service->enabled) {
				$maxPageId = $moduleSettings->service->pageId > $maxPageId ? $moduleSettings->service->pageId : $maxPageId;
			}
			$this->model->installPages($maxPageId);
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal orders
	 * @param string $lockPrefix
	 */
	private function installOrders($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$states = [];
			$types = [];
			$orderSettings = $this->settings->modules->order;
			if ($orderSettings->enabled) {
				$states = (array) $orderSettings->states;
				$types = (array) $orderSettings->types;
			}
			$this->model->installOrders($states, $types);
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	// </editor-fold>
	// <editor-fold desc="lock functions">

	/**
	 * Lock file if locking is set AND lock is unused
	 * Return TRUE if lock is FREE, return FALSE if lock is used
	 * @param string $name
	 * @return boolean
	 */
	private function lock($name)
	{
		if ($this->isLocked($name)) {
			return FALSE;
		} else {
			if ($this->lock) {
				file_put_contents($this->getLockFile($name), self::LOCK_FILE_CONTENT);
			}
			return TRUE;
		}
	}

	/**
	 * Check if lock is used
	 * @param string $name
	 * @return boolean
	 */
	private function isLocked($name)
	{
		return file_exists($this->getLockFile($name));
	}

	/**
	 * Return lock name from inserted method name
	 * @param string $method
	 * @return string
	 */
	private function getLockName($method)
	{
		$lockName = self::LOCK_UNNAMED;
		if (preg_match('~::install(.+)$~i', $method, $matches)) {
			$lockName = $matches[1];
		}
		return $lockName;
	}

	/**
	 * Return lock filename
	 * @param string $name
	 * @return string
	 */
	private function getLockFile($name)
	{
		Helpers::mkDir($this->installDir);
		return $this->installDir . DIRECTORY_SEPARATOR . $name . '.lock';
	}

	// </editor-fold>
}
