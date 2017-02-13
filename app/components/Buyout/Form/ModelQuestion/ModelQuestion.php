<?php

namespace App\Components\Buyout\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Buyout\ModelQuestion as ModelQuestionEntity;
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
		$domain = 'buyout.modelQuestion.';
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicHorizontalFormRenderer(4, 8));

		$form->addText('buyoutPrice', $domain . 'input.price')
			->setRequired($domain . 'required.price')
			->getControlPrototype()->class[] = 'mask_currency input-small';

		$questionRepo = $this->em->getRepository(Question::getClassName());
		$questions = [NULL => $domain . 'notSelect'] + $questionRepo->findPairsTranslate($this->translator->getLocale(), 't.text');
		$boolQuestions = $questionRepo->findPairs(['type' => Question::BOOL], 'id');
		$radioQuestions = $questionRepo->findPairs(['type' => Question::RADIO], 'id');

		$addDynamics = function (Container $container) use ($questions, $domain, $boolQuestions, $radioQuestions) {
			$name = $this->translator->translate($domain . 'input.question', NULL, ['number' => $container->name + 1]);
			$select = $container->addSelect('question', $name, $questions);
			$select->getControlPrototype()->class[] = 'select2';
			$boolCondition = $select->addCondition(Form::IS_IN, array_values($boolQuestions));
			$radioCondition = $select->addCondition(Form::IS_IN, array_values($radioQuestions));

			$answers = $container->addContainer('answers');

			$id = 'answer-yes-' . $container->name;
			$boolCondition->toggle($id);
			$answers->addText('yes', $domain . 'input.yes')
				->setOption('id', $id)
				->getControlPrototype()->class[] = 'mask_currency input-small';

			$id = 'answer-no-' . $container->name;
			$boolCondition->toggle($id);
			$answers->addText('no', $domain . 'input.no')
				->setOption('id', $id)
				->getControlPrototype()->class[] = 'mask_currency input-small';

			for ($i = 1; $i <= Question::ANSWERS_COUNT; $i++) {
				$id = 'answer-num-' . $i . '-' . $container->name;
				$radioCondition->toggle($id);
				$answers->addText($i, $this->translator->translate($domain . 'input.answer', NULL, ['number' => $i]))
					->setOption('id', $id)
					->getControlPrototype()->class[] = 'mask_currency input-small';
			}

		};
		$dynamics = $form->addDynamic('questions', $addDynamics, self::QUESTION_LIMIT);

		$dynamics->addSubmit('add', $domain . 'input.add')
			->setValidationScope(FALSE)
			->onClick[] = $this->addQuestionClicked;

		$form->addSubmit('save', $domain . 'input.save')
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

	public function setModel(ProducerModel $model)
	{
		$this->model = $model;
		$this->model->setCurrentLocale($this->translator->getLocale());

		if (!$this['form']->isSubmitted()) {
			$key = 0;
			foreach ($this->model->questions as $modelQuestion) {
				/** @var $modelQuestion ModelQuestionEntity */
				$answers = [
					'yes' => $modelQuestion->priceYes,
					'no' => $modelQuestion->priceNo,
				];
				for ($i = 1; $i <= Question::ANSWERS_COUNT; $i++) {
					$attr = 'price' . $i;
					$answers[$i] = $modelQuestion->$attr;
				}
				$this['form']['questions'][$key]->setValues([
					'question' => $modelQuestion->question->id,
					'answers' => $answers,
				]);
				$key++;
			}
		}

		$this['form']->setDefaults([
			'buyoutPrice' => $model->buyoutPrice,
		]);

		return $this;
	}

	public function getQuestions()
	{
		$questionRepo = $this->em->getRepository(Question::getClassName());
		$questions = [];
		foreach ($questionRepo->findAll() as $question) {
			/** @var Question $question */
			$question->setCurrentLocale($this->translator->getLocale());
			$questions[$question->id] = $question->answersArray;
		}
		return $questions;
	}

	private function processData(Form $form, ArrayHash $values)
	{
		$old = $this->model->questions;
		$keep = [];

		foreach ($form['questions']->values as $container) {

			if ($container->question) {
				$questionRepo = $this->em->getRepository(Question::getClassName());
				$question = $questionRepo->find($container->question);
				if ($question) {
					$modelQuestionRepo = $this->em->getRepository(ModelQuestionEntity::getClassName());
					$modelQuestion = $modelQuestionRepo->findOneBy([
						'question' => $question,
						'model' => $this->model,
					]);

					if ($modelQuestion) {
						$keep[$question->id] = $modelQuestion;
					} else {
						$modelQuestion = new ModelQuestionEntity();
						$modelQuestion->question = $question;
						$modelQuestion->model = $this->model;
					}

					if ($question->isBool()) {
						$modelQuestion->setPriceBool($container->answers->yes, $container->answers->no);
					} else if ($question->isRadio()) {
						$prices = [];
						for ($i = 1; $i <= Question::ANSWERS_COUNT; $i++) {
							$prices[$i] = $container->answers->$i;
						}
						$modelQuestion->setPriceRadio($prices);
					}
					$this->em->persist($modelQuestion);
				}
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
