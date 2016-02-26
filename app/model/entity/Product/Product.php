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
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\ProductListener"})
 *
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $perex
 * @property Seo $seo
 * @property mixed $createdBy
 * @property mixed $updatedBy
 * @property mixed $deletedBy
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DateTime $deletedAt
 * @property boolean $active
 * @property Unit $unit
 * @property Producer $producer
 * @property ProducerLine $producerLine
 * @property ProducerModel $producerModel
 * @property Category $mainCategory
 * @property array $categories
 * @property array $accessoriesFor
 * @property ArrayCollection $similars
 * @property ArrayCollection $similarsWithMe
 * @property ArrayCollection $stocks
 * @property Stock $stock Default stock item
 */
class Product extends BaseTranslatable
{

	use Model\Translatable\Translatable;
	use Model\Blameable\Blameable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;
	use Traits\ProductCategories;
	use Traits\ProductParameters;
	use Traits\ProductSigns;
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
		$this->accessoriesFor = new ArrayCollection();
		$this->similars = new ArrayCollection();
		$this->similarsWithMe = new ArrayCollection();
		$this->images = new ArrayCollection();
		$this->signs = new ArrayCollection();
		parent::__construct($currentLocale);
	}
	
	public function getStock()
	{
		return $this->stocks->first();
	}
	
	public function getUrl()
	{
		$url = NULL;
		if ($this->mainCategory) {
			$this->mainCategory->setCurrentLocale($this->getCurrentLocale());
			$url = Helpers::getUrlPath($this->mainCategory->url, $this->slug);
		}
		return $url;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function &__get($name)
	{
		$types = Helpers::concatArray(Parameter::getAllowedTypes(), '|');
		if (preg_match('/^parameter([' . $types . ']\d+)$/', $name, $matches)) {
			$value = $this->getParameter($matches[1]);
			return $value;
		} else {
			return parent::__get($name);
		}
	}

}
