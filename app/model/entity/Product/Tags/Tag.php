<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\TagRepository")
 *
 * @property string $name
 * @property string $type
 */
class Tag extends BaseTranslatable
{
	
	const TYPE_TAG = 'tag';
	const TYPE_SIGN = 'sign';

	use Model\Translatable\Translatable;
	
	/** @ORM\Column(type="string", length=20, nullable=false) */
	protected $type = self::TYPE_TAG;
	
	/** @ORM\OneToMany(targetEntity="Product", mappedBy="tags") */
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

}
