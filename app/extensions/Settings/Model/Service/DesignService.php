<?php

namespace App\Extensions\Settings\Model\Service;

use App\Model\Entity\PageDesignSettings;

/**
 * @property-read PageDesignSettings $settings Default settings extended by user settings
 * @property-read PageDesignSettings $userSettings User settings
 * @property-read array $colors Allowed colors
 * @property-write string $color
 * @property-write bool $sidebarClosed
 */
class DesignService extends BaseService
{

	/** @return PageDesignSettings */
	public function getSettings()
	{
		$defaultSettings = new PageDesignSettings();
		$defaultSettings->setValues((array) $this->defaultStorage->design);
		if ($this->defaultStorage->loggedIn && $this->user && $this->user->pageDesignSettings) {
			$settings = $this->user->pageDesignSettings;
			$settings->append($defaultSettings);
		} else {
			$settings = $defaultSettings;
		}
		return $settings;
	}

	/** @return array */
	public function getColors()
	{
		return $this->defaultStorage->design->colors;
	}

	/** @return PageDesignSettings */
	public function getUserSettings()
	{
		if (!$this->user->pageDesignSettings) {
			$this->user->pageDesignSettings = new PageDesignSettings;
		}
		return $this->user->pageDesignSettings;
	}

	public function isAllowedColor($color)
	{
		return array_key_exists($color, $this->colors);
	}

	public function setColor($color)
	{
		if ($this->isAllowedColor($color) && $this->user) {
			$pageDesignSettings = $this->getUserSettings();
			if ($color === 'default') {
				$color = NULL;
			}
			$pageDesignSettings->color = $color;
			$this->saveUser();
		}
		return $this;
	}

	public function setSidebarClosed($value = TRUE)
	{
		if ($this->user) {
			$pageDesignSettings = $this->getUserSettings();
			$pageDesignSettings->sidebarClosed = $value;
			$this->saveUser();
		}
		return $this;
	}

}
