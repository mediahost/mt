<?php

namespace App\AppModule\Presenters;

use App\Components\Parameter\Form\IParameterEditFactory;
use App\Components\Parameter\Form\ParameterEdit;
use App\Components\Parameter\Grid\IParametersGridFactory;
use App\Model\Entity\Parameter;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class ParametersPresenter extends BasePresenter
{

	/** @var Parameter */
	private $parameterEntity;

	/** @var EntityRepository */
	private $parameterRepo;

	// <editor-fold desc="injects">

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
		$lastUnusedCode = $this->parameterFacade->getLastUnusedCode($type);
		if (!$lastUnusedCode) {
			$message = $this->translator->translate('You have reached the maximum of free parameters for this type.');
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}		
		$this->parameterEntity = new Parameter($lastUnusedCode);
		$this['parameterEditForm']->setParameter($this->parameterEntity);
		$this->setView('edit');
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
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Parameter')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['parameterEditForm']->setParameter($this->parameterEntity);
		}
	}
	
	public function renderEdit()
	{
		$this->template->parameter = $this->parameterEntity;
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
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Parameter')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->parameterRepo->delete($parameter);
				$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('Parameter')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDelete', NULL, ['name' => $this->translator->translate('Parameter')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return ParameterEdit */
	public function createComponentParameterEditForm()
	{
		$control = $this->iParameterEditFactory->create();
		$control->onAfterSave = $this->afterSave;
		return $control;
	}

	public function afterSave(Parameter $savedParameter)
	{
		$message = $this->translator->translate('successfullySaved', NULL, [
			'type' => $this->translator->translate('Parameter'), 'name' => (string) $savedParameter
		]);
		$this->flashMessage($message, 'success');
		$this->redirect('default');
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return ParametersGrid */
	public function createComponentParametersGrid()
	{
		$control = $this->iParametersGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
