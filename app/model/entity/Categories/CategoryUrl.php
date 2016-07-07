<?php

namespace App\Model\Entity;

use App\Helpers;

trait CategoryUrl
{

	/**
	 * Return url path with parents and actual category
	 * @return string
	 */
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

	public function getUrlId()
	{
		return $this->id;
	}

}
