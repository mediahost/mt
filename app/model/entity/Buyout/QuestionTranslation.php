<?php

namespace App\Model\Entity\Buyout;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_question_translation")
 *
 * @property string $text
 * @property string $notice
 */
class QuestionTranslation extends BaseEntity
{

	use Model\Translatable\Translation;

	/** @ORM\Column(type="text") */
	protected $text;

	/** @ORM\Column(type="text", nullable=true) */
	protected $notice;

}
