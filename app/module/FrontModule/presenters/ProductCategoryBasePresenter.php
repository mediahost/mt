<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Category;

abstract class ProductCategoryBasePresenter extends BasePresenter
{

	/**
	 * Active category ID
	 * @var int @persistent
	 */
	public $c;

	/** @var Category */
	protected $activeCategory;

	protected function setActiveCategory(Category $category)
	{
		$this->activeCategory = $category;
		$this->c = $category->id;
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->activeCategory = $this->activeCategory;
	}

}
