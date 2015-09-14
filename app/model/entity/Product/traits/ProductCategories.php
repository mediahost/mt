<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Category;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;

/**
 * @property Producer $producer
 * @property ProducerLine $producerLine
 * @property ProducerModel $producerModel
 * @property Category $mainCategory
 * @property array $categories
 */
trait ProductCategories
{

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="products") */
	protected $producer;

	/** @ORM\ManyToOne(targetEntity="ProducerLine", inversedBy="products") */
	protected $producerLine;

	/** @ORM\ManyToOne(targetEntity="ProducerModel", inversedBy="products") */
	protected $producerModel;

	/** @ORM\ManyToOne(targetEntity="Category") */
	protected $mainCategory;

	/** @ORM\ManyToMany(targetEntity="Category", inversedBy="products") */
	protected $categories;

	/** @ORM\ManyToMany(targetEntity="ProducerModel", inversedBy="products") */
	protected $accessoriesFor;
	
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

	public function setAccessoriesFor(array $models)
	{
		$removeIdles = function ($key, ProducerModel $model) use ($models) {
			if (!in_array($model, $models, TRUE)) {
				$this->removeAccessoryModel($model);
			}
			return TRUE;
		};
		$this->accessoriesFor->forAll($removeIdles);
		
		foreach ($models as $model) {
			if ($model instanceof ProducerModel) {
				$this->addAccessoryModel($model);
			}
		}
		return $this;
	}

	public function clearAccessoriesFor()
	{
		return $this->accessoriesFor->clear();
	}

	public function addAccessoryModel(ProducerModel $model)
	{
		if (!$this->accessoriesFor->contains($model)) {
			$this->accessoriesFor->add($model);
		}
		return $this;
	}

	public function removeAccessoryModel(ProducerModel $model)
	{
		return $this->accessoriesFor->removeElement($model);
	}

	public function isInCategory($category)
	{
		if ($category instanceof Category) {
			$categoryId = $category->id;
		} else {
			$categoryId = $category;
		}
		foreach ($this->categories as $category) {
			if ($category->id == $categoryId) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public function isInCategories(array $categories)
	{
		foreach ($categories as $category) {
			if ($this->isInCategory($category)) {
				return TRUE;
			}
		}
		return FALSE;
	}

}