<?php

namespace App\Model\Entity\Buyout;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Entity\BaseTranslatable;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\BuyoutQuestionRepository")
 * @ORM\Table(name="buyout_question")
 *
 * @property string $text
 * @property string $notice
 */
class Question extends BaseTranslatable
{

	use Model\Translatable\Translatable;

	public function __toString()
	{
		return (string)$this->text;
	}

}
