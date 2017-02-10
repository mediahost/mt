<?php

namespace App\Model\Entity\Buyout;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_answer_translation")
 *
 * @property string $text
 */
class AnswerTranslation extends BaseEntity
{

	use Model\Translatable\Translation;

	/** @ORM\Column(type="text") */
	protected $text;

}
