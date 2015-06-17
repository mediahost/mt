<?php

namespace App\Components\Producer\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Producer;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class ProducersGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Producer::getClassName());
		$qb = $repo->createQueryBuilder('p');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'name' => 'ASC',
		]);

		$grid->addColumnText('name', 'Producer')
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

interface IProducersGridFactory
{

	/** @return ProducersGrid */
	function create();
}
