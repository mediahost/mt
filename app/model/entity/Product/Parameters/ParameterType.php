<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property string $name
 */
class ParameterType extends BaseTranslatable
{

	const SELECT = 'select';
	const STRING = 'string';
	const NUMBER = 'number';
	const BOOL = 'bool';

	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=20) */
	protected $type = self::STRING;
	
	/** @ORM\OneToMany(targetEntity="Parameter", mappedBy="type") */
	protected $parameters;
	
	/** @ORM\OneToMany(targetEntity="ParameterValue", mappedBy="type") */
	protected $values;
	
	public function __construct($name = NULL, $currentLocale = NULL)
	{
		parent::__construct($currentLocale);
		if ($name) {
			$this->name = $name;
			$this->mergeNewTranslations();
		}
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
