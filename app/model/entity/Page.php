<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PageRepository")
 *
 * @property string $name
 * @property string $html
 * @property string $comment
 */
class Page extends BaseTranslatable
{
	
	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=10) */
	protected $comment;

	public function __toString()
	{
		return (string) $this->name;
	}
	
	public function isNew()
	{
		return $this->id === NULL;
	}

}
