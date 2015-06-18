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
	}

	public function setCategories(array $categories, Category $mainCategory = NULL)
	{
		$dontDeleteCategories = $categories;
		if ($mainCategory) {
			$dontDeleteCategories[] = $mainCategory;
		}
		$removeIdles = function ($key, Category $category) use ($dontDeleteCategories) {
			if (!in_array($category, $dontDeleteCategories, TRUE)) {
				$this->removeCategory($category);
			}
			return TRUE;
		};
		$this->categories->forAll($removeIdles);
		
		if ($mainCategory) {
			$this->setMainCategory($mainCategory);
		}
		foreach ($categories as $category) {
			if ($category instanceof Category) {
				$this->addCategory($category);
			}
		}
		return $this;
	}

	public function clearCategories()
	{
		return $this->categories->clear();
	}

	public function addCategory(Category $category)
	{
		if (!$this->mainCategory) {
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