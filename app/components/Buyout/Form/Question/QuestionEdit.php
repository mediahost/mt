<?php

namespace App\Components\Buyout\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Buyout\Question;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class QuestionEdit extends BaseControl
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

		$checkedType = $form->addCheckSwitch('type', 'More answers', 'Yes', 'No')
			->addCondition(Form::EQUAL, TRUE);

		$form->addText('text', 'Text')
			->setRequired('Text is required');

		$form->addWysiHtml('notice', 'Notice', 10)
			->getControlPrototype()->class[] = 'page-html-content';

		$answers = $form->addContainer('answer');
		for ($i = 1; $i <= Question::ANSWERS_COUNT; $i++) {
			$id = 'answer-' . $i;
			$checkedType->toggle($id);
			$answers->addText($i, $this->translator->translate('Answer #%number%', NULL, ['number' => $i]))
				->setOption('id', $id);
		}

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
			->setNotice($values->notice);

		if ($values->type && $values->answer && current($values->answer)) {
			$this->entity->type = Question::RADIO;
			// TODO: add answers
		} else {
			$this->entity->type = Question::BOOL;
		}

		$this->entity->mergeNewTranslations();
		$this->em->persist($this->entity)
			->flush();

		$message = $this->translator->translate('successfullySaved', NULL, [
			'type' => $this->translator->translate('Question'), 'name' => (string)$this->entity
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
			'text' => $question->text,
		]);

		return $this;
	}

}

interface IQuestionEditFactory
{

	/** @return QuestionEdit */
	function create();
}
