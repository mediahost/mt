<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property string $html
 */
class ModelParameterTranslation extends BaseEntity
{

	use Model\Translatable\Translation;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\Column(type="text", nullable=true) */
	protected $text;

}
