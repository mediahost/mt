<?php

namespace App\Model\Entity;

use App\Helpers;
use Nette\Utils\Json;

trait CategoryBase
{

	/**
	 * Returns array of parents without this category
	 * - in order from this to top parent
	 * @param bool $onlyIds
	 * @return array
	 */
	public function getParents($onlyIds = FALSE)
	{
		$isTranslatable = property_exists($this, 'currentLocale');
		$parent = $this->parent;
		$path = [];
		$containExistingEdge = FALSE;
		while ($parent !== NULL && !$containExistingEdge) {
			if ($parent->id === $this->id || array_key_exists($parent->id, $path)) {
				$containExistingEdge = TRUE;
			} elseif (!$onlyIds && $isTranslatable) {
				$parent->setCurrentLocale($this->currentLocale);
				$path[$parent->id] = $parent;
			} else {
				if ($onlyIds) {
					$path[$parent->id] = $parent->id;
				} else {
					$path[$parent->id] = $parent;
				}
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
	public function getPath($reverse = FALSE)
	{
		return $reverse ? $this->getParents() : array_reverse($this->getParents());
	}

	/**
	 * Returns reversed parents plus this category
	 * - in order from top to this category
	 * @return array
	 */
	public function getPathWithThis($reverse = FALSE)
	{
		if ($reverse) {
			$path = [
				$this->id => $this,
			];
			$path += $this->getPath($reverse);
		} else {
			$path = $this->getPath($reverse);
			$path[$this->id] = $this;
		}
		return $path;
	}

	public function getIdPath($encodeJson = TRUE)
	{
		$ids = [];
		foreach ($this->getPathWithThis() as $category) {
			$ids[$category->id] = $category->id;
		}
		return $encodeJson ? Json::encode($ids) : $ids;
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

	/**
	 * Return name with path to root parent
	 */
	public function getTreeName($glue = '/', $reverse = FALSE)
	{
		$path = $this->getPathWithThis($reverse);
		return Helpers::concatStrings($glue, $path);
	}

}
