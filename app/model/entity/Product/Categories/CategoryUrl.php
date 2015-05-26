<?php

namespace App\Model\Entity;

use App\Helpers;

trait CategoryUrl
{

	public function getPath($reverse = TRUE)
	{
		$parent = $this->parent;
		$path = [];
		$containExistingEdge = FALSE;
		while ($parent !== NULL && !$containExistingEdge) {
			if ($parent->id === $this->id || array_key_exists($parent->id, $path)) {
				$containExistingEdge = TRUE;
			} else {
				$path[$parent->id] = $parent;
			}
			$parent = $parent->parent;
		}
		if ($reverse) {
			return array_reverse($path);
		}
		return $path;
	}

	public function getUrl()
	{
		$glue = '/';
		$urlPath = [];
		foreach ($this->getPath() as $parent) {
			$urlPath[] = $parent->slug;
		}
		$urlPath[] = $this->slug;
		return Helpers::concatStrings($glue, $urlPath);
	}

}
