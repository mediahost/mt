<?php

namespace App\AppModule\Presenters;

use App\Components\Buyout\Form\IQuestionEditFactory;
use App\Components\Buyout\Form\QuestionEdit;
use App\Components\Buyout\Grid\IQuestionsGridFactory;
use App\Model\Entity\Buyout\Question;

class QuestionPresenter extends BasePresenter
{

	/** @var IQuestionsGridFactory @inject */
	public $iQuestionsGridFactory;

	/** @var IQuestionEditFactory @inject */
	public $iQuestionEditFactory;

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$question = $this->em->getRepository(Question::getClassName())->find($id);

		$this['form']->setEntity($question);
	}

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$repository = $this->em->getRepository(Question::getClassName());
		$entity = $repository->find($id);
		$name = $this->translator->translate('Question');

		if (!$entity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $name]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$repository->delete($entity);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $name]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $name]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	/** @return GridControl */
	public function createComponentGrid()
	{
		return $this->iQuestionsGridFactory->create();
	}

	/** @return QuestionEdit */
	public function createComponentForm()
	{
		return $this->iQuestionEditFactory->create();
	}

}
