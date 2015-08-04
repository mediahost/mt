<?php

namespace App\Components\Question;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Buyout\Question;
use Nette\Utils\ArrayHash;

class EntityControl extends BaseControl
{

	/** @var Question */
	private $entity;

	/** @var array */
	public $onAfterSave = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addText('text', 'Text')
				->setRequired('Text is required');

		$form->addText('choiceA', 'Choice A')
				->setRequired('Choice A is required');

		$form->addText('choiceB', 'Choice B')
				->setRequired('Choice B is required');

		$form->addSubmit('save', 'Save');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceeded(Form $form, $values)
	{
		if (!$this->entity) {
			$this->entity = new Question();
			$locale = $this->translator->getDefaultLocale();
		} else {
			$locale = $this->translator->getLocale();
		}

		$this->entity->translateAdd($locale)
				->setText($values->text)
				->setChoiceA($values->choiceA)
				->setChoiceB($values->choiceB);

		$this->entity->mergeNewTranslations();
		$this->em->persist($this->entity)
				->flush();

		$message = $this->translator->translate('successfullySaved', NULL, [
			'type' => $this->translator->translate('Question'), 'name' => (string) $this->entity
		]);
		$this->presenter->flashMessage($message, 'success');
		$this->presenter->redirect('default');
	}

	/**
	 * @param Question $question
	 * @return EntityControl
	 */
	public function setEntity(Question $question)
	{
		$this->entity = $question;
		$this->entity->setCurrentLocale($this->translator->getLocale());

		$this['form']->setDefaults([
			'text' => $question->text,
			'choiceA' => $question->choiceA,
			'choiceB' => $question->choiceB,
		]);

		return $this;
	}

}

interface IEntityControlFactory
{

	/** @return EntityControl */
	function create();
}
