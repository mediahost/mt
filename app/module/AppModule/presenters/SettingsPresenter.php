<?php

namespace App\AppModule\Presenters;

use App\Components\Currency\Form\IRateFactory;
use App\Components\Currency\Form\Rate;
use App\Components\Unit\Form\IUnitsEditFactory;
use App\Components\Unit\Form\UnitsEdit;
use App\Model\Entity\Unit;
use Kdyby\Doctrine\EntityRepository;

class SettingsPresenter extends BasePresenter
{

	/** @var EntityRepository */
	private $unitRepo;

	// <editor-fold desc="injects">

	/** @var IRateFactory @inject */
	public $iRateFormFactory;

	/** @var IUnitsEditFactory @inject */
	public $iUnitControlFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->unitRepo = $this->em->getRepository(Unit::getClassName());
	}

	/**
	 * @secured
	 * @resource('settings')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('settings')
	 * @privilege('units')
	 */
	public function actionUnits()
	{
		
	}

	// <editor-fold desc="forms">

	/** @return Rate */
	public function createComponentRateForm()
	{
		$control = $this->iRateFormFactory->create();
		$control->onAfterSave = function () {
			$this->flashMessage('Rates was successfully saved.', 'success');
			$this->redirect('default');
		};
		return $control;
	}

	/** @return UnitsEdit */
	public function createComponentUnitsForm()
	{
		$control = $this->iUnitControlFactory->create();
		$control->setUnits($this->unitRepo->findAll());
		$control->setLang($this->locale);
		$control->onAfterSave = function () {
			$this->flashMessage('Units was successfully saved.', 'success');
			$this->redirect('units');
		};
		return $control;
	}

	// </editor-fold>
}
