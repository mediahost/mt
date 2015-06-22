<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\SignRepository")
 *
 * @property string $name
 */
class Sign extends BaseTranslatable
{

	use Model\Translatable\Translatable;

	/** @ORM\OneToMany(targetEntity="Product", mappedBy="signs") */
	protected $products;

	public function __construct($name = NULL, $currentLocale = NULL)
	{
		parent::__construct($currentLocale);
		if ($name) {
			$this->name = $name;
		}
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
