<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Category;
use App\Model\Entity\Producer;

/**
 * @property Producer $producer
 * @property Category $mainCategory
 * @property array $categories
 */
trait ProductCategories
{

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="products") */
	protected $producer;

	/** @ORM\ManyToOne(targetEntity="Category") */
	protected $mainCategory;

	/** @ORM\ManyToMany(targetEntity="Category", inversedBy="products") */
	protected $categories;
	
	public function setMainCategory(Category $category)
	{
		$this->mainCategory = $category;
		$this->addCategory($category);
		foreach ($category->parents as $parent) {
			$this->addCategory($parent);
		}
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

}