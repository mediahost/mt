<?php

namespace Test\Examples\Model\Entity\Asociation\ManyToOneUnidirectional;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 *
 * @property string $mail
 * @property Address $address
 */
class User extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=false, unique=true) */
	protected $mail;
	
    /**
     * @ORM\ManyToOne(targetEntity="Address")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     **/
    protected $address;

}
