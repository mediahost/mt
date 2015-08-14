<?php

namespace App\Components\Buyout\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Buyout\ModelQuestion;
use App\Model\Entity\Buyout\Question;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\QuestionFacade;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;

class ModelQuestion extends BaseControl
{

	const QUESTION_LIMIT = 5;

	/** @var QuestionFacade @inject */
	public $questionFacade;

	/** @var ProducerModel */
	private $model;

	/** @var array */
	public $onAfterSave = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
//		$form->getElementPrototype()->class = 'ajax'; // TODO: formulář si sám nastavuje hodnoty a nevíme odkud je bere!

		$form->addText('buyoutPrice', 'Buyout price')
						->setRequired('Base buyout price is required.')
						->getControlPrototype()->class[] = 'mask_currency form-control input-small';

		$questions = $form->addDynamic('questions', function (Container $question) {
			$question->addTypeahead('text', 'Question', function ($query) {
						return $this->questionFacade->suggestByText($query, $this->translator->getLocale());
					})
					->setAttribute('autocomplete', 'off');

			$question->addText('yes', 'Yes')
							->getControlPrototype()->class[] = 'mask_currency form-control input-small';
			
			$question->addText('no', 'No')
							->getControlPrototype()->class[] = 'mask_currency form-control input-small';
		}, 5);

		$questions->addSubmit('add', 'Add next question')
						->setValidationScope(FALSE)
				->onClick[] = $this->addQuestionClicked;

		$form->addSubmit('save', 'Save')
						->getControlPrototype()->class[] = 'btn-primary';

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		if ($form['save']->isSubmittedBy()) {
			$this->processData($form, $values);
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('Parameter'), 'name' => 'Question'
			]);
			$this->presenter->flashMessage($message, 'success');
			$this->redirect('this');
		}
	}

	public function addQuestionClicked(SubmitButton $button)
	{
		$button->parent->createOne();
	}

	/**
	 * @param ProducerModel $model
	 * @return ModelQuestion
	 */
	public function setModel(ProducerModel $model)
	{
		$this->model = $model;
		$this->model->setCurrentLocale($this->translator->getLocale());

		if (!$this['form']->isSubmitted()) {
			$i = 0;

			foreach ($this->model->questions as $mq) {
				$this['form']['questions'][$i]->setValues([
					'text' => $mq->question->text,
					'yes' => $mq->priceA,
					'no' => $mq->priceB,
				]);
				$i++;
			}
		}

		$this['form']->setDefaults([
			'buyoutPrice' => $model->buyoutPrice,
		]);

		return $this;
	}

	private function processData(Form $form, ArrayHash $values)
	{
		$old = $this->model->questions;
		$keep = [];

		foreach ($form['questions']->values as $container) {
			if ($container['text'] != NULL) {
				$question = $this->questionFacade->findOneByText($container['text'], $this->translator->getLocale());

				if (!$question) {
					$locale = $this->translator->getDefaultLocale();

					$question = new Question();
					$question->translateAdd($locale)
							->setText($container['text'])
							->setChoiceA($this->translator->translate('Yes'))
							->setChoiceB($this->translator->translate('No'));

					$question->mergeNewTranslations();
					$this->em->persist($question);
					$modelQuestion = NULL;
				} else {
					$modelQuestion = $this->em->getRepository(ModelQuestion::getClassName())->findOneBy([
						'question' => $question,
						'model' => $this->model,
					]);
				}

				if ($modelQuestion) {
					$keep[$question->id] = $modelQuestion;
				} else {
					$modelQuestion = new ModelQuestion();
					$modelQuestion->setQuestion($question)
							->setModel($this->model);
				}

				$modelQuestion->setPrice($container['yes'], $container['no']);
				$this->em->persist($modelQuestion);
			}
		}

		$this->model->buyoutPrice = $values->buyoutPrice;
		$this->em->persist($this->model);

		foreach ($old as $mq) {
			if (!isset($keep[$mq->question->id])) {
				$this->em->remove($mq);
			}
		}

		$this->em->flush();
	}

}

interface IModelQuestionFactory
{

	/** @return ModelQuestion */
	function create();
}
