<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\MappedSuperclass()
 * @method void setCurrentLocale(mixed $locale) the current locale
 */
abstract class BaseTranslatable extends BaseEntity
{

	use Identifier;

	public function __construct($currentLocale = NULL)
	{
		parent::__construct();
		if ($currentLocale) {
			$this->setCurrentLocale($currentLocale);
		}
	}

	public function __call($method, $arguments)
	{
		return $this->proxyCurrentLocaleTranslation($method, $arguments);
	}
	
	public function translateAdd($lang)
	{
		return $this->translate($lang, FALSE);
	}

	/**
	 * For all tranlation property redirect $this->property to $this->getProperty()
	 * Because translation property can call only by ->getProperty() method
	 */
	public function &__get($name)
	{
		try {
			$value = $this->getTranslationProperty($name);
			return $value; // return must be variable, because PHP notice
		} catch (BaseTranslatableException $e) {
			return parent::__get($name);
		}
	}

	/**
	 * For all tranlation property redirect $this->property = $value to $this->setProperty($value)
	 * Because translation properties can set only by setProperty()
	 */
	public function __set($name, $value)
	{
		try {
			return $this->setTranslationProperty($name, $value);
		} catch (BaseTranslatableException $e) {
			parent::__set($name, $value);
		}
	}

	private function getTranslationProperty($name)
	{
		if (self::isTranslationProperty($name)) {
			return static::__call('get' . ucfirst($name), []);
		} else {
			throw new BaseTranslatableException;
		}
	}

	private function setTranslationProperty($name, $value)
	{
		if (self::isTranslationProperty($name)) {
			$method = 'set' . ucfirst($name);
			return $this->$method($value);
		} else {
			throw new BaseTranslatableException;
		}
	}

	static private function isTranslationProperty($property)
	{
		return !self::isBehaviorProperty($property) && property_exists(static::getTranslationEntityClass(), $property);
	}

	static private function isBehaviorProperty($property)
	{
		return property_exists('Knp\DoctrineBehaviors\Model\Translatable\TranslationProperties', $property);
	}

}

class BaseTranslatableException extends \Exception
{

	public function __construct()
	{
		parent::__construct('Requested property isn\'t in translation entity');
	}

}
