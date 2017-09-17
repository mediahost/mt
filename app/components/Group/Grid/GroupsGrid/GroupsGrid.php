<?php

namespace App\Components\Group\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Group;
use Exception;
use Grido\DataSources\Doctrine;
use Grido\Grid;
use Nette\Security\User;

class GroupsGrid extends BaseControl
{

	/** @var User @inject */
	public $user;
	private $groupType;

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Group::getClassName());
		$qb = $repo->createQueryBuilder('g');
		if ($this->groupType) {
			$qb->whereCriteria(['type' => $this->groupType]);
		}
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'level' => 'ASC',
		]);

		$grid->addColumnNumber('level', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('level')->headerPrototype->width = '5%';

		$grid->addColumnText('name', 'Name')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		if ($this->isBonusType()) {
			$grid->addColumnNumber('percentage', 'Discount')
					->setCustomRender(function (Group $item) {
						return ($item->percentage ? $item->percentage : '0') . '%';
					})
					->setSortable()
					->setFilterNumber();
		}


		$allowEdit = !$this->isBonusType() || $this->user->isAllowed('groups', 'editBonus');
		$grid->addActionHref('edit', 'Edit')
				->setIcon('fa fa-edit')
				->setDisable(function ($item) use ($allowEdit) {
					return !$allowEdit;
				});

		$allowDelete = !$this->isBonusType();
		$button = $grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setDisable(function ($item) use ($allowDelete) {
							return !$allowDelete;
						})
						->setConfirm(function($item) {
							$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
							return $message;
						});
		$button->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth("20%");

		return $grid;
	}

	public function setType($type)
	{
		switch ($type) {
			case Group::TYPE_DEALER:
			case Group::TYPE_BONUS:
				$this->groupType = $type;
				break;
			default:
				throw new Exception('This type is not allowed');
		}
	}

	private function isBonusType()
	{
		return $this->groupType === Group::TYPE_BONUS;
	}

}

interface IGroupsGridFactory
{

	/** @return GroupsGrid */
	function create();
}
