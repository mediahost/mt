<?php

namespace App\AppModule\Presenters;

use App\Components\Question\GridControl;
use App\Components\Question\IEntityControlFactory;
use App\Components\Question\IGridControlFactory;
use App\Model\Entity\Buyout\Question;
use Kdyby\Doctrine\DBALException;

class QuestionPresenter extends BasePresenter
{

	/**
	 * @var IGridControlFactory @inject
	 */
	public $iGridControlFactory;

	/**
	 * @var IEntityControlFactory @inject
	 */
	public $iEntityControlFactory;

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
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $name]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$repository->delete($entity);
				$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $name]);
				$this->flashMessage($message, 'success');
			} catch (DBALException $e) {
				$message = $this->translator->translate('cannotDelete', NULL, ['name' => $name]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	/** @return GridControl */
	public function createComponentGrid()
	{
		return $this->iGridControlFactory->create();
	}

	/** @return  */
	public function createComponentForm()
	{
		$control = $this->iEntityControlFactory->create();
		return $control;
	}

}
