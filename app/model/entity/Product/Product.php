<?php

namespace App\Model\Entity;

use App\Helpers;
use App\Model\Entity\Traits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
 *
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $perex
 * @property ProductSeo $seo
 * @property mixed $createdBy
 * @property mixed $updatedBy
 * @property mixed $deletedBy
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DateTime $deletedAt
 * @property boolean $active
 * @property Unit $unit
 * @property Producer $producer
 * @property Category $mainCategory
 * @property array $categories
 * @property ArrayCollection $similars
 * @property ArrayCollection $similarsWithMe
 * @property ArrayCollection $stocks
 * @property Stock $stock Default stock item
 */
class Product extends BaseTranslatable
{

	use Model\Translatable\Translatable;
	use Model\Blameable\Blameable;
	use Model\Loggable\Loggable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;
	use Traits\ProductCategories;
	use Traits\ProductParameters;
	use Traits\ProductSimilars;
	use Traits\ProductImages;

	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;
	
    /** @ORM\ManyToOne(targetEntity="Unit") */
	protected $unit;
	
	/** @ORM\OneToMany(targetEntity="Stock", mappedBy="product") */
	protected $stocks;

	public function __construct($currentLocale = NULL)
	{
		$this->stocks = new ArrayCollection();
		$this->categories = new ArrayCollection();
		$this->similars = new ArrayCollection();
		$this->similarsWithMe = new ArrayCollection();
		$this->images = new ArrayCollection();
		parent::__construct($currentLocale);
	}
	
	public function getStock()
	{
		return $this->stocks->first();
	}
	
	public function getUrl()
	{
		$this->mainCategory->setCurrentLocale($this->getCurrentLocale());
		$url = Helpers::getPath($this->mainCategory->url, $this->slug);
		return $url;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
