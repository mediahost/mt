<?php

namespace App\Components\Discount\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Voucher;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class VouchersGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Voucher::getClassName());
		$qb = $repo->createQueryBuilder('v')
			->select('v');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'activeTo' => 'DESC',
		]);

		$grid->addColumnText('code', 'Code')
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('code')->headerPrototype->width = '10%';

		$grid->addColumnNumber('value', 'Value')
			->setCustomRender(function (Voucher $item) {
				return $item->getValueString(NULL, $this->exchange);
			})
			->setSortable()
			->setFilterNumber();

		$grid->addColumnText('type', 'Type')
			->setCustomRender(function (Voucher $item) {
				return $this->translator->translate($item->type);
			})
			->setFilterSelect(Voucher::getTypesArray());
		$grid->getColumn('type')->headerPrototype->width = '8%';

		$grid->addColumnBoolean('active', 'Active');

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
				return $message;
			})
			->setDisable(function ($item) {
				return !$this->presenter->canDelete($item);
			})
			->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth("20%");

		return $grid;
	}

}

interface IVouchersGridFactory
{

	/** @return VouchersGrid */
	function create();
}
