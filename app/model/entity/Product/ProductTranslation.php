<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
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

	/** @ORM\OneToOne(targetEntity="ProductSeo", mappedBy="product", cascade={"persist", "remove"}) * */
	protected $seo;

	public function getSeo()
	{
		if (!$this->seo) {
			$this->seo = new ProductSeo();
			$this->seo->product = $this;
		}
		return $this->seo;
	}

	protected function getSluggableFields()
	{
		return ['name'];
	}

}
