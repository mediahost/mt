<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ParameterRepository")
 *
 * @property string $name
 */
class Parameter extends BaseTranslatable
{

	/** Dont change type contstants without change Product parameter names */
	const STRING = 'S';
	const INTEGER = 'N';
	const BOOLEAN = 'B';

	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=5) */
	protected $type;

	/** @var array */
	private $types = [];

	public function __construct($type, $name = NULL, $currentLocale = NULL)
	{
		$this->setType($type);
		parent::__construct($currentLocale);
		if ($name) {
			$this->name = $name;
		}
	}

	public function setType($type)
	{
		$types = $this->getTypes();
		if (in_array($type, $types)) {
			$this->type = $type;
		} else {
			throw new EntityException('Parameter of type ' . $type . ' isn\'t supported by ' . Product::getClassName() . ' entity.');
		}
	}

	public function getTypes()
	{
		if (!count($this->types)) {
			$this->types = self::getProductTypes();
		}
		return $this->types;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public static function getProductTypes()
	{
		$types = [];
		foreach (Product::getParameterProperties() as $property) {
			if (preg_match('/^parameter(\w\d+)$/', $property->name, $matches)) {
				$types[] = $matches[1];
			}
		}
		return $types;
	}

}
