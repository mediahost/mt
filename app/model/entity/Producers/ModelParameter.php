<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ModelParameterRepository")
 *
 * @property string $name
 * @property string $html
 */
class ModelParameter extends BaseTranslatable
{

	use Model\Translatable\Translatable;

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

}
