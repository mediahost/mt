<?php

namespace App\Components\Grids\Group;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Helpers;
use App\Model\Entity\Group;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class GroupsGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Group::getClassName());
		$qb = $repo->createQueryBuilder('g');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'name' => 'ASC',
		]);

		$grid->addColumnNumber('id', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		$grid->addColumnText('name', 'Group')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addActionHref('edit', 'Edit')
						->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setConfirm(function($item) {
							$message = $this->translator->translate('Are you sure you want to delete \'%s\'?');
							return sprintf($message, (string) $item);
						})
				->elementPrototype->class[] = 'red';
						
		$grid->setActionWidth("20%");

		return $grid;
	}

}

interface IGroupsGridFactory
{

	/** @return GroupsGrid */
	function create();
}
