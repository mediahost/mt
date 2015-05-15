<?php

namespace App\Extensions\Settings\Model\Storage;

use App\Model\Entity\User;
use Nette\Object;
use Nette\Utils\ArrayHash;

/**
 * @property ArrayHash $expiration Expiration default settings
 * @property ArrayHash $languages Languages default settings
 * @property ArrayHash $passwords Passwords default settings
 * @property ArrayHash $design Design default settings
 * @property ArrayHash $pageConfig Page Controls default settings
 * @property ArrayHash $pageInfo Page Info default settings
 * @property-read ArrayHash $modules Allowed modules
 * @property-read ArrayHash $moduleSettings Settings of modules
 * @property User $user Signed user
 * @property bool $loggedIn If identity logged in
 * @property GuestSettingsStorage $guest Unsigned settings
 */
class DefaultSettingsStorage extends Object
{

	/** @var ArrayHash */
	private $expiration;

	/** @var ArrayHash */
	private $languages;

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

	/** @var User */
	private $user;

	/** @var bool */
	private $isLoggedIn;

	/** @var GuestSettingsStorage */
	private $guest;

	/** @return self */
	public function setExpiration(array $expiration)
	{
		$this->expiration = ArrayHash::from($expiration);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getExpiration()
	{
		return $this->expiration;
	}

	/** @return self */
	public function setLanguages(array $languages)
	{
		$this->languages = ArrayHash::from($languages);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getLanguages()
	{
		return $this->languages;
	}

	/** @return self */
	public function setPasswords(array $passwords)
	{
		$this->passwords = ArrayHash::from($passwords);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getPasswords()
	{
		return $this->passwords;
	}

	/** @return self */
	public function setDesign(array $design)
	{
		$this->design = ArrayHash::from($design);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getDesign()
	{
		return $this->design;
	}

	/** @return self */
	public function setPageConfig(array $controls)
	{
		$this->pageConfig = ArrayHash::from($controls);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getPageConfig()
	{
		return $this->pageConfig;
	}

	/** @return self */
	public function setPageInfo(array $info)
	{
		$this->pageInfo = ArrayHash::from($info);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getPageInfo()
	{
		return $this->pageInfo;
	}

	/**
	 * Set modules allowing and module settings
	 * @param array $modules
	 * @param array $settings
	 * @return self
	 */
	public function setModules(array $modules, array $settings)
	{
		$this->modules = ArrayHash::from($modules);
		$this->modulesSettings = ArrayHash::from($settings);
		return $this;
	}
	
	/** @return ArrayHash */
	public function getModules()
	{
		return $this->modules;
	}
	
	/** @return ArrayHash */
	public function getModuleSettings()
	{
		return $this->modulesSettings;
	}
	
	/** @return self */
	public function setUser(User $user)
	{
		$this->user = $user;
		return $this;
	}
	
	/** @return User */
	public function getUser()
	{
		return $this->user;
	}
	
	/** @return self */
	public function setLoggedIn($isLoggedIn)
	{
		$this->isLoggedIn = $isLoggedIn;
		return $this;
	}
	
	/** @return bool */
	public function getLoggedIn()
	{
		return $this->isLoggedIn;
	}
	
	/** @return self */
	public function setGuest(GuestSettingsStorage $guest)
	{
		$this->guest = $guest;
		return $this;
	}
	
	/** @return GuestSettingsStorage */
	public function getGuest()
	{
		return $this->guest;
	}

}
