<?php

namespace App\Model\Facade;

use Nette\Application\Request;
use Nette\Object;

class UriFacade extends Object
{

	const LOCALE_PARAM = 'locale';

	/** @var array */
	private $presenters = [
		'cs' => [
			'Page' => 'Strana',
			'Service' => 'Servis',
		],
		'sk' => [
			'Page' => 'Strana',
			'Service' => 'Servis',
		],
	];

	/** @var array */
	private $actions = [
		'cs' => [
		],
		'sk' => [
		],
	];

	private function getPresenterTranslations($locale)
	{
		if (array_key_exists($locale, $this->presenters)) {
			return $this->presenters[$locale];
		}
		return [];
	}

	private function getActionTranslations($locale)
	{
		if (array_key_exists($locale, $this->actions)) {
			return $this->actions[$locale];
		}
		return [];
	}

	public function presenterToName($presenter, Request $request)
	{
		$locale = $request->getParameter(self::LOCALE_PARAM);
		$translations = $this->getPresenterTranslations($locale);
		return $this->getKeyValue($presenter, $translations);
	}

	public function nameToPresenter($translation, Request $request)
	{
		$locale = $request->getParameter(self::LOCALE_PARAM);
		$translations = $this->getPresenterTranslations($locale);
		return $this->getSearchedKey($translation, $translations);
	}

	public function actionToName($action, Request $request)
	{
		$locale = $request->getParameter(self::LOCALE_PARAM);
		$translations = $this->getActionTranslations($locale);
		return $this->getKeyValue($action, $translations);
	}

	public function nameToAction($translation, Request $request)
	{
		$locale = $request->getParameter(self::LOCALE_PARAM);
		$translations = $this->getActionTranslations($locale);
		return $this->getSearchedKey($translation, $translations);
	}

	private function getSearchedKey($needle, array $haystack)
	{
		$searched = array_search($needle, $haystack);
		if ($searched) {
			return $searched;
		}
		return $needle;
	}

	private function getKeyValue($key, array $haystack)
	{
		if (array_key_exists($key, $haystack)) {
			return $haystack[$key];
		}
		return $key;
	}

}
