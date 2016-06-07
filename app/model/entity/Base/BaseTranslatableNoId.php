<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby\Doctrine\MemberAccessException;

/**
 * @ORM\MappedSuperclass()
 * @method void setCurrentLocale(mixed $locale) the current locale
 */
abstract class BaseTranslatableNoId extends BaseEntity
{
	
	const DEFAULT_LOCALE = 'sk';
	
	protected $defaultLocale = self::DEFAULT_LOCALE;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;

	public function __construct($currentLocale = NULL, $id = NULL)
	{
		if ($id) {
			$this->id = $id;
		}
		parent::__construct();
		if ($currentLocale) {
			$this->setCurrentLocale($currentLocale);
		}
	}

	public function setId($id)
	{
		throw MemberAccessException::propertyNotWritable('a read-only', $this, 'id');
	}

	public function __call($method, $arguments)
	{
		return $this->proxyCurrentLocaleTranslation($method, $arguments);
	}
	
	public function translateAdd($locale)
	{
		return $this->translate($locale, FALSE);
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
			throw new BaseTranslatableException();
		}
	}

	private function setTranslationProperty($name, $value)
	{
		if (self::isTranslationProperty($name)) {
			$method = 'set' . ucfirst($name);
			return $this->$method($value);
		} else {
			throw new BaseTranslatableException();
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
