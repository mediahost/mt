<?php

namespace App\AppModule\Presenters;

use App\Components\Producer\Grid\ProducersGrid;
use App\Components\Producer\Grid\IProducersGridFactory;
use App\Components\Producer\Form\ProducerEdit;
use App\Components\Producer\Form\IProducerEditFactory;
use App\Model\Entity\Producer;
use App\TaggedString;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class ProducersPresenter extends BasePresenter
{

	/** @var Producer */
	private $producerEntity;

	/** @var EntityRepository */
	private $producerRepo;

	// <editor-fold desc="injects">

	/** @var IProducerEditFactory @inject */
	public $iProducerEditFactory;

	/** @var IProducersGridFactory @inject */
	public $iProducersGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->producerRepo = $this->em->getRepository(Producer::getClassName());
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->producerEntity = new Producer();
		$this['producerForm']->setProducer($this->producerEntity);
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->producerEntity = $this->producerRepo->find($id);
		if (!$this->producerEntity) {
			$this->flashMessage('This producer wasn\'t found.', 'warning');
			$this->redirect('default');
		} else {
			$this['producerForm']->setProducer($this->producerEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->isAdd = $this->producerEntity->isNew();
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$user = $this->producerRepo->find($id);
		if (!$user) {
			$this->flashMessage('Producer wasn\'t found.', 'danger');
		} else {
			try {
				$this->producerRepo->delete($user);
				$this->flashMessage('User was deleted.', 'success');
			} catch (Exception $e) {
				$this->flashMessage('This producer can\'t be deleted.', 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return ProducerEdit */
	public function createComponentProducerForm()
	{
		$control = $this->iProducerEditFactory->create();
		$control->onAfterSave = function (Producer $savedProducer) {
			$message = new TaggedString('Producer \'%s\' was successfully saved.', (string) $savedProducer);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return ProducersGrid */
	public function createComponentProducersGrid()
	{
		$control = $this->iProducersGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
