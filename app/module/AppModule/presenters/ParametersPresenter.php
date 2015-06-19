<?php

namespace App\AppModule\Presenters;

use App\Components\Parameter\Form\IParameterAddFactory;
use App\Components\Parameter\Form\IParameterEditFactory;
use App\Components\Parameter\Form\ParameterAdd;
use App\Components\Parameter\Form\ParameterEdit;
use App\Components\Parameter\Grid\IParametersGridFactory;
use App\Model\Entity\Parameter;
use App\TaggedString;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class ParametersPresenter extends BasePresenter
{

	/** @var Parameter */
	private $parameterEntity;

	/** @var EntityRepository */
	private $parameterRepo;

	// <editor-fold desc="injects">

	/** @var IParameterAddFactory @inject */
	public $iParameterAddFactory;

	/** @var IParameterEditFactory @inject */
	public $iParameterEditFactory;

	/** @var IParametersGridFactory @inject */
	public $iParametersGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->parameterRepo = $this->em->getRepository(Parameter::getClassName());
	}

	/**
	 * @secured
	 * @resource('parameters')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('parameters')
	 * @privilege('add')
	 */
	public function actionAdd($type = Parameter::STRING)
	{
		switch ($type) {
			case Parameter::STRING:
			case Parameter::INTEGER:
			case Parameter::BOOLEAN:
				break;
			default:
				$type = Parameter::STRING;
				break;
		}
		$lastUnusedNumber = $this->parameterFacade->getLastUnusedNumber($type);
		if (!$lastUnusedNumber) {
			$this->flashMessage('You have reached the maximum of free parameters for this type.', 'warning');
			$this->redirect('default');
		}		
		$lastUnused = $type . $lastUnusedNumber;
		$this->parameterEntity = new Parameter($lastUnused);
		$this['parameterAddForm']->setParameter($this->parameterEntity);
	}

	/**
	 * @secured
	 * @resource('parameters')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->parameterEntity = $this->parameterRepo->find($id);
		if (!$this->parameterEntity) {
			$this->flashMessage('This parameter wasn\'t found.', 'warning');
			$this->redirect('default');
		} else {
			$this['parameterEditForm']->setParameter($this->parameterEntity);
		}
	}

	/**
	 * @secured
	 * @resource('parameters')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$parameter = $this->parameterRepo->find($id);
		if (!$parameter) {
			$this->flashMessage('Parameter wasn\'t found.', 'danger');
		} else {
			try {
				$this->parameterRepo->delete($parameter);
				$this->flashMessage('Parameter was deleted.', 'success');
			} catch (Exception $e) {
				$this->flashMessage('This parameter can\'t be deleted.', 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return ParameterAdd */
	public function createComponentParameterAddForm()
	{
		$control = $this->iParameterAddFactory->create();
		$control->onAfterSave = $this->afterSave;
		return $control;
	}

	/** @return ParameterEdit */
	public function createComponentParameterEditForm()
	{
		$control = $this->iParameterEditFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterSave;
		return $control;
	}

	public function afterSave(Parameter $savedParameter)
	{
		$message = new TaggedString('Parameter \'%s\' was successfully saved.', (string) $savedParameter);
		$this->flashMessage($message, 'success');
		$this->redirect('default');
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return ParametersGrid */
	public function createComponentParametersGrid()
	{
		$control = $this->iParametersGridFactory->create();
		$control->setLang($this->lang);
		return $control;
	}

	// </editor-fold>
}
