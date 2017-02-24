<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PageRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\PageListener"})
 *
 * @property string $name
 * @property string $html
 * @property string $comment
 * @property string $link
 * @property string $linkHeadline
 * @property string $linkSubscribe
 * @property Shop $shop
 * @property ShopVariant $shopVariant
 */
class Page extends BaseTranslatable
{
	
	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $comment;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $link;

	/** @ORM\ManyToOne(targetEntity="Shop") */
	protected $shop;

	/** @ORM\ManyToOne(targetEntity="ShopVariant") */
	protected $shopVariant;
	
	public function __construct($currentLocale = NULL, $id = NULL)
	{
		if ($id) {
			$this->setId($id);
		}
		parent::__construct($currentLocale);
	}
	
	public function isInterLink()
	{
		$isExtern = preg_match('@(^http|^/\w+|\./)@', $this->link);
		return $this->link && !$isExtern;
	}

	public function __toString()
	{
		return (string) $this->name;
	}
	
	public function isNew()
	{
		return $this->id === NULL;
	}
	
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

}
