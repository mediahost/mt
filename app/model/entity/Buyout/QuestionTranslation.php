<?php

namespace App\Model\Entity\Buyout;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_question_translation")
 */
class QuestionTranslation extends BaseEntity
{

	use \Knp\DoctrineBehaviors\Model\Translatable\Translation;

	/** @ORM\Column(type="text") */
	protected $text;
	
	/** @ORM\Column(type="text") */
	protected $choiceA;
	
	/** @ORM\Column(type="text") */
	protected $choiceB;
}
