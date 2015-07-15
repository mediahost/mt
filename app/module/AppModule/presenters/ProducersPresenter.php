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
use App\TaggedString;
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
					$this->flashMessage('Wrong ID format.', 'warning');
					$this->redirect('default');
					break;
			}
		}
		if (isset($repo)) {
			$this->entity = $repo->find($itemId);
			if (!$this->entity) {
				$message = new TaggedString('This %s wasn\'t found.', $this->getEntityTypeName());
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
			$this->flashMessage('This parameter wasn\'t found.', 'warning');
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
			$this->flashMessage('Parameter wasn\'t found.', 'danger');
		} else {
			try {
				$parameterRepo->delete($this->parameter);
				$this->flashMessage('Parameter was deleted.', 'success');
			} catch (Exception $e) {
				$this->flashMessage('This Parameter can\'t be deleted.', 'danger');
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
		$control->setLang($this->locale);
		$control->onAfterSave = function ($saved, $type, $addNext) {
			$typeName = Strings::firstUpper($this->getEntityTypeName($type));
			$message = new TaggedString($typeName . ' \'%s\' was successfully saved.', (string) $saved);
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
		$control->setLang($this->locale);
		$control->onAfterSave = function (ModelParameter $saved) {
			$message = new TaggedString('Parameter \'%s\' was successfully saved.', (string) $saved);
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
		$control->setLang($this->locale);
		return $control;
	}

	// </editor-fold>
}
