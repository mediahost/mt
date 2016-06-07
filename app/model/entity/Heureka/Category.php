<?php

namespace App\Model\Entity\Heureka;

use App\Model\Entity\BaseTranslatableNoId;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\Table(name="heureka_category")
 *
 * @property int $id
 * @property string $name
 * @property string $fullname
 */
class Category extends BaseTranslatableNoId
{

	use Model\Translatable\Translatable;

	public function __toString()
	{
		return (string)$this->name;
	}

}
