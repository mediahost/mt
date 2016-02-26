<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\ProductListener"})
 *
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $perex
 * @property Seo $seo
 */
class ProductTranslation extends BaseEntity
{

	use Model\Translatable\Translation;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\Column(type="text", nullable=true) */
	protected $description;

	/** @ORM\Column(type="text", nullable=true) */
	protected $perex;

	/** @var Seo */
	private $seo; // must be here, because BaseTranslatable search getters by properties

	/** @ORM\Column(type="string", length=55, nullable=true) */
	private $seoName;

	/** @ORM\Column(type="text", nullable=true) */
	private $seoKeywords;

	/** @ORM\Column(type="text", nullable=true) */
	private $seoDescription;

	public function setName($value)
	{
		$oldName = $this->name;
		$this->name = $value;
		if ($oldName != $this->name) {
			if ($this->translatable->stock) {
				$this->translatable->stock->setChangePohodaData();
			}
		}
		return $this;
	}

	public function getSeo()
	{
		if (!$this->seo) {
			$this->seo = new Seo();
		}
		$this->seo->name = $this->seoName;
		$this->seo->keywords = $this->seoKeywords;
		$this->seo->description = $this->seoDescription;

		return $this->seo;
	}

	public function setSeo(Seo $seo)
	{
		$this->seoName = $seo->name;
		$this->seoKeywords = $seo->keywords;
		$this->seoDescription = $seo->description;

		return $this;
	}

	protected function getSluggableFields()
	{
		return ['name'];
	}

}
