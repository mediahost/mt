<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby\Doctrine\MemberAccessException;

/**
 * @ORM\MappedSuperclass
 *
 * @property string $id
 */
class OAuth extends BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=255)
	 * @var string
	 */
	protected $id;

	public function setId($id)
	{
		throw MemberAccessException::propertyNotWritable('a read-only', $this, 'id');
	}

}
