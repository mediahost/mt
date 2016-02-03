<?php

namespace App\Components\Group\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Group;
use App\Model\Facade\StockFacade;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class GroupEdit extends BaseControl
{

	/** @var Group */
	private $group;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @var User @inject */
	public $user;

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		if ($this->group->isDealerType() || $this->user->isAllowed('groups')) {
			$form->addText('name', 'Name')
					->setRequired('Name is required');
		}

		if ($this->group->isBonusType() && $this->user->isAllowed('groups', 'editBonus')) {
			$form->addText('percentage', 'Discount')
					->setRequired('Discount is required')
					->setAttribute('class', ['mask_percentage']);
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
		$this->onAfterSave($this->group);
	}

	private function load(ArrayHash $values)
	{
		if (isset($values->name) && $values->name) {
			$this->group->name = $values->name;
		}
		if (isset($values->percentage) && $values->percentage) {
			$this->group->percentage = (float) $values->percentage;
		}
		return $this;
	}

	private function save()
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groupRepo->save($this->group);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->group->name,
			'percentage' => $this->group->percentage,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->group) {
			throw new BaseControlException('Use setGroup(\App\Model\Entity\Group) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setGroup(Group $group)
	{
		$this->group = $group;
		return $this;
	}

	// </editor-fold>
}

interface IGroupEditFactory
{

	/** @return GroupEdit */
	function create();
}
