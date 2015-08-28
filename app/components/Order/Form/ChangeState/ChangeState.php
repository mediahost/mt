<?php

namespace App\Components\Order\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Facade\OrderFacade;
use Nette\Utils\ArrayHash;

class ChangeState extends BaseControl
{

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var Order */
	private $order;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$stateRepo = $this->em->getRepository(OrderState::getClassName());
		$states = $stateRepo->findPairs('name');
		$form->addSelect2('state', 'State', $states);

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->order);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		$this->orderFacade->changeState($this->order, $values->state);
		return $this;
	}

	private function save()
	{
		$orderRepo = $this->em->getRepository(Order::getClassName());
		$orderRepo->save($this->order);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'state' => $this->order->state->id,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->order) {
			throw new BaseControlException('Use setOrder(\App\Model\Entity\Order) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setOrder(Order $order)
	{
		$this->order = $order;
		return $this;
	}

	// </editor-fold>
}

interface IChangeStateFactory
{

	/** @return ChangeState */
	function create();
}
