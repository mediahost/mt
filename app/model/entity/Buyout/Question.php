<?php

namespace App\Model\Entity\Buyout;

use App\Model\Entity\BaseTranslatable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Translation\Translator;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\BuyoutQuestionRepository")
 * @ORM\Table(name="buyout_question")
 *
 * @property string $text
 * @property string $notice
 * @property string $type
 * @property string $formatedType
 * @property array $answersArray
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

	/** @ORM\OneToMany(targetEntity="Answer", mappedBy="question", cascade={"persist", "remove"}, orphanRemoval=true) */
	private $answers;

	public function __construct($currentLocale = NULL)
	{
		parent::__construct($currentLocale);
		$this->answers = new ArrayCollection();
	}

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

	public function addAnswer($id, Translator $translator, $text)
	{
		$existed = FALSE;
		$editAnswer = function ($key, Answer $answer) use ($id, $translator, $text, &$existed) {
			if ($key + 1 == $id) {
				$translation = $answer->translate($translator->getLocale());
				if ($translation->locale !== $translator->getLocale()) {
					$translation = $answer->translateAdd($translator->getLocale());
				}
				$translation->text = $text;
				$answer->mergeNewTranslations();
				$existed = TRUE;
			}
			return !$existed;
		};
		$this->answers->forAll($editAnswer);
		if (!$existed) {
			$answer = new Answer($translator->getDefaultLocale());
			$answer->question = $this;
			$translation = $answer->translateAdd($translator->getDefaultLocale());
			$translation->text = $text;
			$answer->mergeNewTranslations();
			$this->answers->add($answer);
		}
		return $this;
	}

	public function removeAnswer($id, Translator $translator, $removeAllLocale = TRUE)
	{
		$deleteAnswer = function ($key, Answer $answer) use ($id, $translator, $removeAllLocale) {
			if ($key + 1 == $id) {
				$translation = $answer->translate($translator->getLocale());
				if ($removeAllLocale || $translation->locale === $translator->getDefaultLocale()) {
					$this->answers->remove($key);
				} else {
					$answer->removeTranslation($translation);
				}
				return FALSE;
			}
			return TRUE;
		};
		$this->answers->forAll($deleteAnswer);
		return $this;
	}

	public function getAnswerItem($key)
	{
		if ($this->isRadio()) {
			foreach ($this->answers as $keyOrigin => $answer) {
				/** @var $answer Answer */
				$answer->setCurrentLocale($answer->question->getCurrentLocale());
				if ($key == ($keyOrigin + 1)) {
					return (string)$answer;
				}
			}
		}
		return NULL;
	}

	public function getAnswersArray()
	{
		$answers = [];
		foreach ($this->answers as $key => $answer) {
			/** @var $answer Answer */
			$answer->setCurrentLocale($answer->question->getCurrentLocale());
			$answers[$key + 1] = (string)$answer;
		}
		return $this->isRadio() ? $answers : NULL;
	}

	public static function getTypes()
	{
		return [
			self::BOOL => 'Yes/No',
			self::RADIO => 'Various options',
		];
	}

}
