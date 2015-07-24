<?php

namespace App\Extensions\Settings;

use Nette\Object;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

/**
 * @author Martin Šifra <me@martinsifra.cz>
 */
class SettingsStorage extends Object
{

	/** @var User */
	private $user;

	/** @var ArrayHash */
	private $expiration;

	/** @var ArrayHash */
	private $passwords;

	/** @var ArrayHash */
	private $design;

	/** @var ArrayHash */
	private $pageConfig;

	/** @var ArrayHash */
	private $pageInfo;

	/** @var ArrayHash */
	private $modules;

	/** @var ArrayHash */
	private $modulesSettings;

	public function __construct(User $user)
	{
		$this->user = $user;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setExpiration($values)
	{
		$this->expiration = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getExpiration()
	{
		return $this->expiration;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setPasswords($values)
	{
		$this->passwords = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getPasswords()
	{
		return $this->passwords;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setDesign($values)
	{
		$this->design = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getDesign()
	{
		if ($this->user->identity->sidebarClosed !== NULL) {
			Debugger::barDump('Není NULL');
			$this->design->sidebarClosed = $this->user->identity->sidebarClosed;
		} else {
			Debugger::barDump('Je NULL');
		}

		return $this->design;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setPageConfig($values)
	{
		$this->pageConfig = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getPageConfig()
	{
		return $this->pageConfig;
	}

	/**
	 * @param array $values
	 * @return SettingsStorage
	 */
	public function setPageInfo(array $values)
	{
		$this->pageInfo = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getPageInfo()
	{
		return $this->pageInfo;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setModules(array $values)
	{
		$this->modules = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getModules()
	{
		return $this->modules;
	}

}
