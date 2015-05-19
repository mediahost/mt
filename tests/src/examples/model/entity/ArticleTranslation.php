<?php

namespace Test\Examples\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * https://github.com/Zenify/DoctrineBehaviors#translatable
 *
 * @property string $name
 */
class ArticleTranslation extends BaseEntity
{

	use Model\Translatable\Translation;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $title;

}
