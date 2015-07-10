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
 * @property string $link
 */
class Page extends BaseTranslatable
{
	
	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=100) */
	protected $comment;

	/** @ORM\Column(type="string", length=256) */
	protected $link;
	
	public function isInterLink()
	{
		$isExtern = preg_match('/(^http|^//\w+|\.//)/', $this->link);
		return !$isExtern;
	}

	public function __toString()
	{
		return (string) $this->name;
	}
	
	public function isNew()
	{
		return $this->id === NULL;
	}

}
