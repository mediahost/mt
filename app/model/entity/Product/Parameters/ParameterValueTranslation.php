<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property string $value
 */
class ParameterValueTranslation extends BaseEntity
{

	use Model\Translatable\Translation;

	/** @ORM\Column(type="string", length=100) */
	protected $value;

}
