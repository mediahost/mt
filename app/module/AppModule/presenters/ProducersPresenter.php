<?php

namespace App\AppModule\Presenters;

use App\Components\Producer\Form\IModelParameterEditFactory;
use App\Components\Producer\Form\IProducerEditFactory;
use App\Components\Producer\Form\ProducerEdit;
use App\Components\Producer\Grid\IModelParametersGridFactory;
use App\Components\Producer\Grid\ModelParametersGrid;
use App\Model\Entity\ModelParameter;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Nette\Utils\Strings;

class ProducersPresenter extends BasePresenter
{

	/** @var string */
	private $type;

	/** @var Producer */
	private $entity;

	/** @var ModelParameter */
	private $parameter;

	// <editor-fold desc="injects">

	/** @var IProducerEditFactory @inject */
	public $iProducerEditFactory;

	/** @var IModelParameterEditFactory @inject */
	public $iModelParameterEditFactory;

	/** @var IModelParametersGridFactory @inject */
	public $iModelParametersGridFactory;

	// </editor-fold>

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('default')
	 */
	public function actionDefault($id)
	{
		$itemId = Producer::getItemId($id, $this->type);
		if ($itemId) {
			switch ($this->type) {
				case Producer::ID:
					$repo = $this->em->getRepository(Producer::getClassName());
					break;
				case ProducerLine::ID:
					$repo = $this->em->getRepository(ProducerLine::getClassName());
					break;
				case ProducerModel::ID:
					$repo = $this->em->getRepository(ProducerModel::getClassName());
					break;
				default:
					$message = $this->translator->translate('Wrong ID format.');
					$this->flashMessage($message, 'warning');
					$this->redirect('default');
					break;
			}
		}
		if (isset($repo)) {
			$this->entity = $repo->find($itemId);
			if (!$this->entity) {
				$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->getEntityTypeName()]);
				$this->flashMessage($message, 'warning');
				$this->redirect('default');
			} else {
				$this['producerForm']->setProducer($this->entity);
			}
		}
	}

	public function renderDefault()
	{
		switch ($this->type) {
			case Producer::ID:
			case ProducerLine::ID:
			case ProducerModel::ID:
				$this->template->entity = $this->entity;
				$this->template->entityFullId = $this->type . Producer::SEPARATOR . $this->entity->id;
				break;
			default:
				$this->template->entity = NULL;
				$this->template->entityFullId = NULL;
				break;
		}
		$this->template->entityTypeName = $this->getEntityTypeName();
		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->entity = new Producer();
		$this['producerForm']->setProducer($this->entity);
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('addParameter')
	 */
	public function actionAddParameter()
	{
		$this->parameter = new ModelParameter();
		$this['parameterForm']->setParameter($this->parameter);
		$this->setView('editParameter');
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('editParameter')
	 */
	public function actionEditParameter($id)
	{
		$parameterRepo = $this->em->getRepository(ModelParameter::getClassName());
		$this->parameter = $parameterRepo->find($id);
		if (!$this->parameter) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Parameter')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['parameterForm']->setParameter($this->parameter);
		}
	}

	public function renderEditParameter()
	{
		$this->template->isAdd = $this->parameter->isNew();
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('deleteParameter')
	 */
	public function actionDeleteParameter($id)
	{
		$parameterRepo = $this->em->getRepository(ModelParameter::getClassName());
		$this->parameter = $parameterRepo->find($id);
		if (!$this->parameter) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Parameter')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$parameterRepo->delete($this->parameter);
				$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('Parameter')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDelete', NULL, ['name' => $this->translator->translate('Parameter')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	private function getEntityTypeName($type = NULL)
	{
		if ($type === NULL) {
			$type = $this->type;
		}
		$names = [
			Producer::ID => 'producer',
			ProducerLine::ID => 'line',
			ProducerModel::ID => 'model',
		];
		return array_key_exists($type, $names) ? $names[$type] : NULL;
	}

	// <editor-fold desc="forms">

	/** @return ProducerEdit */
	public function createComponentProducerForm()
	{
		$control = $this->iProducerEditFactory->create();
		$control->onAfterSave = function ($saved, $type, $addNext) {
			$typeName = Strings::firstUpper($this->getEntityTypeName($type));
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate($typeName), 'name' => (string) $saved
			]);
			$this->flashMessage($message, 'success');
			if ($addNext) {
				$this->redirect('add');
			} else {
				$this->redirect('default', ['id' => $type . Producer::SEPARATOR . $saved->id]);
			}
		};
		return $control;
	}

	/** @return ProducerEdit */
	public function createComponentParameterForm()
	{
		$control = $this->iModelParameterEditFactory->create();
		$control->onAfterSave = function (ModelParameter $saved) {
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('Parameter'), 'name' => (string) $saved
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return ModelParametersGrid */
	public function createComponentParametersGrid()
	{
		$control = $this->iModelParametersGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
