<?php

namespace App\Model\Entity;

use App\Helpers;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @property string $name
 * @property string $keywords
 * @property string $description
 */
class Seo extends BaseEntity
{

	/** @var string */
	protected $name;

	/** @var string */
	protected $keywords;

	/** @var string */
	protected $description;

	public function setName($name)
	{
		if (!empty($name)) {
			$this->name = $name;
		}
		return $this;
	}

	public function setKeywords($keywords, $glue = ', ')
	{
		if (is_array($keywords)) {
			$keywords = Helpers::concatArray($keywords, $glue);
		}
		if (!empty($keywords)) {
			$this->keywords = $keywords;
		}
		return $this;
	}

	public function setDescription($description)
	{
		if (!empty($description)) {
			$this->description = $description;
		}
		return $this;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
