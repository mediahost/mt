<?php

namespace App\Extensions\Settings\Model\Service;

use App\Model\Entity\Special\UniversalDataEntity;

/**
 * @property-read string $length Length of password
 */
class ModuleService extends BaseService
{

	/**
	 * Check if name of module is allowed
	 * @param string $name
	 * @return boolean
	 */
	public function isAllowedModule($name)
	{
		if (isset($this->defaultStorage->modules->$name) && $this->defaultStorage->modules->$name === TRUE) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * If setting exists then return universal entity else return NULL
	 * @param string $name
	 * @return UniversalDataEntity|NULL
	 */
	public function getModuleSettings($name)
	{
		if ($this->isAllowedModule($name) && isset($this->defaultStorage->moduleSettings->$name)) {
			return new UniversalDataEntity((array) $this->defaultStorage->moduleSettings->$name);
		}
		return NULL;
	}

}
