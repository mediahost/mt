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
 * @property string $type
 * @property string $formatedType
 */
class Question extends BaseTranslatable
{

	const ANSWERS_COUNT = 5;
	const BOOL = 'bool';
	const RADIO = 'radio';
	const DEFAULT_TYPE = self::BOOL;

	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=10) */
	protected $type = self::DEFAULT_TYPE;

	public function __toString()
	{
		return (string)$this->text;
	}

	public function isBool()
	{
		return $this->isInType(self::BOOL);
	}

	public function isRadio()
	{
		return $this->isInType(self::RADIO);
	}

	public function isInType($type)
	{
		return $this->type === $type;
	}

	public function getFormatedType()
	{
		$types = self::getTypes();
		return array_key_exists($this->type, $types) ? $types[$this->type] : $this->type;
	}

	public static function getTypes()
	{
		return [
			self::BOOL => 'Yes/No',
			self::RADIO => 'Various options',
		];
	}

}
