<?php

namespace App\Components\Product\Form;

use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\ProductSign;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Facade\ParameterFacade;
use Nette\Utils\ArrayHash;

class StockSign extends StockBase
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
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicHorizontalFormRenderer());
		$form->getElementPrototype()->class('ajax');

		$signRepo = $this->em->getRepository(Sign::getClassName());
		$allSigns = $signRepo->findAll();

		$signs = $form->addContainer('signs');
		foreach ($allSigns as $sign) {
			/* @var $sign Sign */
			$sign->setCurrentLocale($this->translator->getLocale());
			$signs->addCheckSwitch($sign->id, $sign->name, 'YES', 'NO');
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

		$signValues = (array) $values->signs;
		$signRepo = $this->em->getRepository(Sign::getClassName());
		$productSignRepo = $this->em->getRepository(ProductSign::getClassName());

		foreach ($signValues as $id => $value) {
			$sign = $signRepo->find($id);
			if ($sign) {
				if ($value) {
					$product->addSign($sign);
				} else {
					$signConn = $productSignRepo->findOneBy([
						'sign' => $sign,
						'product' => $product,
					]);
					if ($signConn) {
						$product->removeSign($signConn);
						$productSignRepo->delete($signConn);
					}
				}
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
		foreach ($this->stock->product->getSigns() as $sign) {
			$values['signs'][$sign->id] = TRUE;
		}
		return $values;
	}

	public function hasSigns()
	{
		$signRepo = $this->em->getRepository(Sign::getClassName());
		$allParams = $signRepo->findAll();
		return (bool) count($allParams);
	}

	public function render()
	{
		$this->template->printForm = $this->hasSigns();
		parent::render();
	}

}

interface IStockSignFactory
{

	/** @return StockSign */
	function create();
}
