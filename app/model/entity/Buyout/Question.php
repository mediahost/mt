<?php

namespace App\Model\Entity\Buyout;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Entity\BaseTranslatable;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_question")
 *
 * @property string $text
 * @property string $choiceA
 * @property string $choiceB
 */
class Question extends BaseTranslatable
{

	use \Knp\DoctrineBehaviors\Model\Translatable\Translatable;

	public function __toString()
	{
		return (string) $this->text;
	}

}
