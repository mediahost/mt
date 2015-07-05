<?php

namespace App\AppModule\Presenters;

use App\Components\Producer\Form\IProducerEditFactory;
use App\Components\Producer\Form\ProducerEdit;
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

	// <editor-fold desc="injects">

	/** @var IProducerEditFactory @inject */
	public $iProducerEditFactory;

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

	// </editor-fold>
}
