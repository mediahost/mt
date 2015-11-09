<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ParameterRepository")
 *
 * @property string $name
 * @property string $code
 * @property string $type
 */
class Parameter extends BaseTranslatable
{

	/** Dont change type contstants without change Product parameter names */
	const STRING = 'S';
	const INTEGER = 'N';
	const BOOLEAN = 'B';

	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=5) */
	protected $code;

	/** @var array */
	private $codes = [];

	public function __construct($type, $name = NULL, $currentLocale = NULL)
	{
		$this->setCode($type);
		parent::__construct($currentLocale);
		if ($name) {
			$this->name = $name;
		}
	}

	public function setCode($code)
	{
		$types = $this->getCodes();
		if (in_array($code, $types)) {
			$this->code = $code;
		} else {
			throw new EntityException('Parameter with code ' . $code . ' isn\'t supported by ' . Product::getClassName() . ' entity.');
		}
	}

	public function getCodes()
	{
		if (!count($this->codes)) {
			$this->codes = self::getProductCodes();
		}
		return $this->codes;
	}

	public function getType()
	{
		if (preg_match('/^(\w)\d+$/', $this->code, $matches)) {
			switch ($matches[1]) {
				case self::STRING:
				case self::INTEGER:
				case self::BOOLEAN:
					return $matches[1];
				default:
					return NULL;
			}
		}
		return NULL;
	}
	
	public function getTypeIsBool()
	{
		return $this->getType() === self::BOOLEAN;
	}
	
	public function isNew()
	{
		return $this->id === NULL;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public static function getProductCodes()
	{
		$codes = [];
		foreach (Product::getParameterProperties() as $property) {
			$codes[] = $property->code;
		}
		return $codes;
	}

	public static function getProductTypesWithCodes()
	{
		$types = [];
		foreach (Product::getParameterProperties() as $property) {
			/* @var $property ParameterProperty */
			$types[$property->type][$property->order] = $property->code;
		}
		return $types;
	}

	public static function getAllowedTypes()
	{
		return [
			self::STRING,
			self::INTEGER,
			self::BOOLEAN,
		];
	}

	public static function checkCodeHasType($code, $type)
	{
		if (!in_array($type, self::getAllowedTypes())) {
			return FALSE;
		}
		$regexpPrefix = '/^';
		$regexpSuffix = '\d+$/';
		return preg_match($regexpPrefix . $type . $regexpSuffix, $code);
	}

}
