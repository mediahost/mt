<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\PageListener"})
 *
 * @property string $name
 * @property string $html
 */
class PageTranslation extends BaseEntity
{

	use Model\Translatable\Translation;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=50, nullable=false) */
	protected $name;

	/** @ORM\Column(type="text", nullable=true) */
	protected $html;

	public function getSluggableFields()
	{
		return ['name'];
	}

}
