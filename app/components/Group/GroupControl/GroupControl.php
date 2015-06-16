<?php

namespace App\Components\Group;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Group;
use Nette\Utils\ArrayHash;

class GroupControl extends BaseControl
{

	/** @var Group */
	private $group;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$form->addText('name', 'Name')
				->setRequired('Name is required');

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
		$this->group->name = $values->name;
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

interface IGroupControlFactory
{

	/** @return GroupControl */
	function create();
}
