<?php

namespace App\Components\Product\Form;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Parameter;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Facade\ParameterFacade;
use Nette\Utils\ArrayHash;

class StockParameter extends StockBase
{
	// <editor-fold desc="variables">

	/** @var ParameterFacade @inject */
	public $parameterFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicHorizontalFormRenderer());

		$paramRepo = $this->em->getRepository(Parameter::getClassName());
		$allParams = $paramRepo->findAll();

		$strings = $form->addContainer(Parameter::STRING);
		$numbers = $form->addContainer(Parameter::INTEGER);
		$bools = $form->addContainer(Parameter::BOOLEAN);
		foreach ($allParams as $param) {
			/* @var $param Parameter */
			$param->setCurrentLocale($this->lang);
			switch ($param->getType()) {
				case Parameter::STRING:
					$strings->addText($param->code, $param->name)
									->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;
					break;
				case Parameter::INTEGER:
					$numbers->addTouchSpin($param->code, $param->name)
							->setSize(MetronicTextInputBase::SIZE_M)
							->setMax(1000000);
					break;
				case Parameter::BOOLEAN:
					$bools->addCheckSwitch($param->code, $param->name, 'YES', 'NO');
					break;
			}
		}

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->stock);
	}

	private function load(ArrayHash $values)
	{
		$product = $this->stock->product;
		$product->clearParameters();

		$parameters = [];
		$parameters += (array) $values->{Parameter::STRING};
		$parameters += (array) $values->{Parameter::INTEGER};
		$parameters += (array) $values->{Parameter::BOOLEAN};

		foreach ($parameters as $type => $value) {
			if ($value !== '') {
				$product->setParameter($type, $value);
			}
		}
		return $this;
	}

	private function save()
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stockRepo->save($this->stock);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];

		foreach (Parameter::getAllowedTypes() as $type) {
			$properties = Product::getParameterProperties($type);
			foreach ($properties as $property) {
				$values[$type][$property->code] = $this->stock->product->$property;
			}
		}
		return $values;
	}

	public function hasParameters()
	{
		$paramRepo = $this->em->getRepository(Parameter::getClassName());
		$allParams = $paramRepo->findAll();
		return (bool) count($allParams);
	}

	public function render()
	{
		$this->template->printForm = $this->hasParameters();
		parent::render();
	}

}

interface IStockParameterFactory
{

	/** @return StockParameter */
	function create();
}
