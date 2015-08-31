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
		$grid->getColumn('id')->headerPrototype->width = '7%';
		
		$stateRepo = $this->em->getRepository(\App\Model\Entity\OrderState::getClassName());
		$grid->addColumnText('state', 'State')
				->setSortable()
				->setCustomRender(__DIR__ . '/tag.latte')
				->setFilterSelect([NULL => '--- anyone ---'] + $stateRepo->findPairs('name'));

		$grid->addColumnText('totalPrice', 'Total price')
				->setCustomRender(function ($item) {
					$toCurrency = $item->currency;
					$totalPrice = $item->getTotalPrice($this->exchange);
					return $this->exchange->formatTo($totalPrice, $toCurrency);
				})
				->setFilterNumber();
		$grid->getColumn('totalPrice')->headerPrototype->width = '10%';
		$grid->getColumn('totalPrice')->cellPrototype->style = 'text-align: right';

		$grid->addColumnDate('createdAt', 'Created At', 'd.m.Y H:i:s')
				->setSortable()
				->setFilterText()
				->setSuggestion();
		$grid->getColumn('createdAt')->headerPrototype->width = '10%';
		$grid->getColumn('createdAt')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('locale', 'Language')
				->setSortable()
				->setFilterText()
				->setSuggestion();
		$grid->getColumn('locale')->headerPrototype->width = '4%';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('currency', 'Currency')
				->setCustomRender(function ($item) {
					return $item->currency . ($item->rate ? ' (' . $item->rate . ')' : '');
				})
				->setSortable()
				->setFilterSelect([
					NULL => '--- anyone ---',
					'CZK' => 'CZK',
					'EUR' => 'EUR',
				]);
		$grid->getColumn('currency')->headerPrototype->width = '7%';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

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

		$grid->setActionWidth("10%");

		return $grid;
	}

}

interface IOrdersGridFactory
{

	/** @return OrdersGrid */
	function create();
}
