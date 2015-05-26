<?php

namespace App\Model\Entity;

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
 * @property ProductSeo $perex
 * @property mixed $createdBy
 * @property mixed $updatedBy
 * @property mixed $deletedBy
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DateTime $deletedAt
 * @property boolean $active
 * @property string $ean
 * @property Producer $producer
 * @property Category $mainCategory
 * @property array $categories
 * @property array $tags
 */
class Product extends BaseTranslatable
{

	use Model\Translatable\Translatable;
	use Model\Blameable\Blameable;
	use Model\Loggable\Loggable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;

	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $ean;

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="products") */
	protected $producer;

	/** @ORM\ManyToOne(targetEntity="Category") */
	protected $mainCategory;

	/** @ORM\ManyToMany(targetEntity="Category", inversedBy="products") */
	protected $categories;

	/** @ORM\ManyToMany(targetEntity="Tag", inversedBy="products") */
	protected $tags;

	public function __construct($currentLocale = NULL)
	{
		$this->categories = new ArrayCollection();
		$this->tags = new ArrayCollection();
		parent::__construct($currentLocale);
	}
	
	public function setMainCategory(Category $category)
	{
		$this->mainCategory = $category;
		$this->addCategory($category);
	}

	public function setCategories(array $categories)
	{
		$removeIdles = function ($key, Category $category) use ($categories) {
			if (!in_array($category, $categories, TRUE)) {
				$this->removeCategory($category);
			}
			return TRUE;
		};
		$this->categories->forAll($removeIdles);
		foreach ($categories as $category) {
			$this->addCategory($category);
		}
		return $this;
	}

	public function addCategory(Category $category)
	{
		if (!$this->categories->contains($this->mainCategory)) {
			$this->mainCategory = $category;
		}
		if (!$this->categories->contains($category)) {
			$this->categories->add($category);
		}
		return $this;
	}

	public function removeCategory(Category $category)
	{
		if ($category === $this->mainCategory) {
			$this->mainCategory = NULL;
		}
		return $this->categories->removeElement($category);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
