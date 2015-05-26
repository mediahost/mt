<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property ParameterType $type
 * @property array $parameters
 */
class ParameterValue extends BaseTranslatable
{
	
	const STRING = 'string';
	const SELECT = 'select';
	const NUMBER = 'number';
	const BOOL = 'bool';

	use Model\Translatable\Translatable;
	
	/** @ORM\OneToMany(targetEntity="Parameter", mappedBy="type") */
	protected $parameters;

	/** @ORM\ManyToOne(targetEntity="ParameterType", inversedBy="values") */
	protected $type;
	
	public function __construct($value = NULL, $currentLocale = NULL)
	{
		parent::__construct($currentLocale);
		if ($value) {
			$this->value = $value;
		}
	}

	public function __toString()
	{
		return (string) $this->value;
	}

}
