<?php

namespace App;

use Kdyby\Translation\Translator;
use LogicException;

class LocaleHelpers
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}

	// <editor-fold desc="strings">

	public static function getLocalesFromTranslator(Translator $translator)
	{
		$locales = [];
		$availableLocales = $translator->getAvailableLocales();
		foreach ($availableLocales as $availableLocale) {
			if (preg_match('@^(\w+)\_@', $availableLocale, $matches)) {
				$locales[] = $matches[1];
			}
		}
		return $locales;
	}

	// </editor-fold>
}
