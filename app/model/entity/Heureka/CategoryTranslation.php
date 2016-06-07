<?php

namespace App\Model\Entity\Heureka;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\Table(name="heureka_category_translation")
 *
 * @property string $name
 * @property string $html
 */
class CategoryTranslation extends BaseEntity
{

	use Model\Translatable\Translation;

	/** @ORM\Column(type="string", length=100, nullable=false) */
	protected $name;

	/** @ORM\Column(type="string", nullable=false) */
	protected $fullname;

}
