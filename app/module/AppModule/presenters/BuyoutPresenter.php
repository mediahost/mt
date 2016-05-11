<?php

namespace App\AppModule\Presenters;

use App\Components\Buyout\Form\IModelQuestionFactory;
use App\Components\Buyout\Form\ModelQuestion;
use App\Model\Entity\ProducerModel;

class BuyoutPresenter extends BasePresenter
{

	/** @var IModelQuestionFactory @inject */
	public $iModelQuestionFactory;

	/**
	 * @secured
	 * @resource('buyout')
	 * @privilege('default')
	 */
	public function actionDefault($modelId = NULL)
	{
		if ($modelId !== NULL) {
			$model = $this->em->getRepository(ProducerModel::getClassName())->find($modelId);

			if (!$model) {
				$message = $this->translator->translate('wasntFound', NULL, ['name' => 'Model TODO']);
				$this->flashMessage($message, 'warning');
				$this->redirect('default');
			} else {
				$this['modelQuestion']->setModel($model);
			}
		} else {
			$model = NULL;
		}

		$this->template->entity = $model;

		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

	/**
	 * @secured
	 * @resource('buyout')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		
	}

	/**
	 * @secured
	 * @resource('buyout')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		
	}

	/**
	 * @secured
	 * @resource('buyout')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		
	}

	/** @return ModelQuestion */
	protected function createComponentModelQuestion()
	{
		return $this->iModelQuestionFactory->create();
	}

}
