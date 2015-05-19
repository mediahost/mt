<?php

namespace Test\Examples\Model\Entity\Asociation\OneToOneSelfReferencing;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 * 
 * @property string $name
 * @property Student $mentor
 */
class Student extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string") */
	protected $name;
	
    /**
     * @ORM\OneToOne(targetEntity="Student")
     * @ORM\JoinColumn(name="mentor_id", referencedColumnName="id")
     **/
    protected $mentor;

}
