<?php

namespace App\Model\Facade;

use Kdyby\Translation\Translator;
use Nette\Object;

class LocaleFacade extends Object
{

	/** @var Translator @inject */
	public $translator;

	public function getLocalesToSelect()
	{
		$locales = ["" => 'default.locales.all'];

		foreach ($this->translator->getAvailableLocales() as $locale) {
			$locales[substr($locale, 0, 2)] = 'default.locales.' . $locale;
		}

		return $locales;
	}

}
