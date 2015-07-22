<?php

namespace App\Model\Entity\Special;

use Nette\Utils\ArrayHash;

/**
 * Return setted data from array
 */
class XmlItem extends ArrayHash
{

	public function __set($tag, $value)
	{
		$name = preg_replace('/:/', '_', $tag);
		$this->{$name} = $value;
	}

}
