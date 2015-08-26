<?php

namespace App\Components\Order\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Order;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class OrdersGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Order::getClassName());
		$qb = $repo->createQueryBuilder('o');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'createdAt' => 'DESC',
		]);

		$grid->addColumnText('id', 'Number')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addColumnDate('createdAt', 'Created At', 'd.m.Y H:i:s')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addColumnText('locale', 'Language')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addActionHref('edit', 'Edit')
				->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setConfirm(function($item) {
							$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
							return $message;
						})
						->setDisable(function($item) {
							return !$this->presenter->canDelete($item);
						})
				->elementPrototype->class[] = 'red';

		$grid->setActionWidth("20%");

		return $grid;
	}

}

interface IOrdersGridFactory
{

	/** @return OrdersGrid */
	function create();
}
