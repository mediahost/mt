<?php

namespace App\Model\Entity\Buyout;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Entity\BaseTranslatable;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_answer")
 *
 * @property string $text
 * @property Question $question
 */
class Answer extends BaseTranslatable
{

	use Model\Translatable\Translatable;

	/** @ORM\ManyToOne(targetEntity="Question", inversedBy="answers") */
	protected $question;

	public function __toString()
	{
		return (string)$this->text;
	}

}
