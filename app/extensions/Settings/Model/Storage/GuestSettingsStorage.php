<?php

namespace App\Extensions\Settings\Model\Storage;

use App\Extensions\Settings\Model\Service\PageConfigService;
use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\PageDesignSettings;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Object;

/**
 * Storage for unsigned user's customization settings
 * 
 * @property PageConfigService $pageSettings
 * @property PageDesignService $designSettings
 * @property-read bool $empty
 */
class GuestSettingsStorage extends Object
{

	/** @var SessionSection */
	private $section;

	/**
	 * @param Session $session
	 */
	public function injectSession(Session $session)
	{
		$this->section = $session->getSection('guestSettings');
		$this->section->warnOnUndefined = TRUE;
	}

	/**
	 * @param PageConfigSettings $settings
	 * @return self
	 */
	public function setPageSettings(PageConfigSettings $settings)
	{
		$this->section->page = $settings;
		return $this;
	}

	/** @return PageConfigSettings|NULL */
	public function getPageSettings()
	{
		if (isset($this->section->page)) {
			return $this->section->page;
		}
		return NULL;
	}

	/**
	 * @param PageDesignSettings $settings
	 * @return self
	 */
	public function setDesignSettings(PageDesignSettings $settings)
	{
		$this->section->design = $settings;
		return $this;
	}

	/** @return PageDesignSettings */
	public function getDesignSettings()
	{
		if (isset($this->section->design)) {
			return $this->section->design;
		}
		return NULL;
	}
	
	public function isEmpty()
	{
		return (!isset($this->section->page) && !isset($this->section->design));
	}

	/** @return self */
	public function wipe()
	{
		unset($this->section->page);
		unset($this->section->design);
		return $this;
	}

}
