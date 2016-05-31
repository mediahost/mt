<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\CategoryListener"})
 *
 * @property string $name
 * @property string $html
 */
class CategoryTranslation extends BaseEntity
{

	use Model\Translatable\Translation;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $name;

	/** @ORM\Column(type="text", nullable=true) */
	protected $html;

	public function getSluggableFields()
	{
		return ['name'];
	}

}
