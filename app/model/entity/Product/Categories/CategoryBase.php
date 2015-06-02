<?php

namespace App\Model\Entity;

trait CategoryBase
{
	
	public function getHasChildren()
	{
		return (bool) count($this->children);
	}
	
	/**
	 * Check if $category in path
	 * @param Category|Producer $category
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

}
