<?php

namespace App\Components\Buyout;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Buyout\ModelQuestion;
use App\Model\Entity\Buyout\Question;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\QuestionFacade;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;

class ModelQuestionControl extends BaseControl
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
		$form->getElementPrototype()->class = 'ajax';

		$form->addText('buyoutPrice', 'Buyout price')
				->setRequired('Base buyout price is required.');

		$form->addDynamic('questions', function (Container $question) {
			$question->addTypeahead('text', 'Question', function ($query) {
						return $this->questionFacade->suggestByText($query, $this->translator->getLocale());
					})
					->setAttribute('autocomplete', 'off');

			$question->addText('yes', 'Yes');
			$question->addText('no', 'No');
		}, 5);

		$form->addSubmit('save', 'Save')
						->getControlPrototype()->class[] = 'btn-primary';

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);

		if ($this->presenter->isAjax()) {
			$this->redrawControl();
		} else {
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('Parameter'), 'name' => 'ToDo'
			]);
			$this->presenter->flashMessage($message, 'success');
			$this->presenter->redirect('default');
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
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
					$modelQuestion = $this->em->getRepository(ModelQuestion::class)->findOneBy([
						'question' => $question,
						'model' => $this->model,
					]);
				}

				if (!$modelQuestion) {
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
		$this->em->flush();

		return $this;
	}

	/**
	 * @param ProducerModel $model
	 * @return ModelQuestionControl
	 */
	public function setModel(ProducerModel $model)
	{
		$this->model = $model;

		if (!$this['form']->isSubmitted()) {
			$questions = $this->questionFacade->findByModel($model, $this->translator->getLocale(), self::QUESTION_LIMIT);
			$i = 0;

			foreach ($questions as $modelQuestion) {
				$this['form']['questions'][$i]->setValues([
					'text' => $modelQuestion->question->text,
					'yes' => $modelQuestion->priceA,
					'no' => $modelQuestion->priceB,
				]);
				$i++;
			}
		}

		$this['form']->setDefaults([
			'buyoutPrice' => $model->buyoutPrice,
		]);

		return $this;
	}

	public function handleRemoveQuestion($mqId)
	{
		
		
	}

}

interface IModelQuestionControlFactory
{

	/** @return ModelQuestionControl */
	function create();
}
