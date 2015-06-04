<?php

namespace App\Model\Entity;

trait CategoryBase
{

	/**
	 * Returns array of parents without this category
	 * - in order from this to top parent
	 * @return array
	 */
	public function getParents()
	{
		$parent = $this->parent;
		$path = [];
		$containExistingEdge = FALSE;
		while ($parent !== NULL && !$containExistingEdge) {
			if ($parent->id === $this->id || array_key_exists($parent->id, $path)) {
				$containExistingEdge = TRUE;
			} else {
				$parent->setCurrentLocale($this->currentLocale);
				$path[$parent->id] = $parent;
			}
			$parent = $parent->parent;
		}
		return $path;
	}

	/**
	 * Returns reversed parents
	 * - in order from top to this category
	 * @return array
	 */
	public function getPath()
	{
		return array_reverse($this->getParents());
	}

	/**
	 * Check if $category is in path
	 * @param Category|Producer $category
	 * @param bool $includeSelf check with this category
	 * @return boolean
	 */
	public function isInPath($category, $includeSelf = TRUE)
	{
		$parent = $this->parent;
		$path = [];
		$containExistingEdge = FALSE;
		$isInPath = $includeSelf && $this->id === $category->id;
		while ($parent !== NULL && !$containExistingEdge && !$isInPath) {
			if ($parent->id === $category->id) {
				$isInPath = TRUE;
			} else if (array_key_exists($parent->id, $path)) {
				$containExistingEdge = TRUE;
			} else {
				$path[$parent->id] = $parent;
			}
			$parent = $parent->parent;
		}
		return $isInPath;
	}

	/**
	 * Check if this category has children
	 * @return bool
	 */
	public function getHasChildren()
	{
		return (bool) count($this->children);
	}

	/**
	 * Returns array of childs and its childs
	 * @param bool $withThis TRUE include this category
	 * @param int $deep only for setted deep NULL for unlimited
	 * @return array
	 */
	public function getChildrenArray($withThis = TRUE, $deep = NULL)
	{
		$array = [];
		if ($withThis) {
			$array[$this->id] = $this;
		}
		if (($deep === NULL || $deep > 0) && $this->hasChildren) {
			foreach ($this->children as $child) {
				$array += $child->getChildrenArray(TRUE, --$deep);
			}
		}
		return $array;
	}

}
