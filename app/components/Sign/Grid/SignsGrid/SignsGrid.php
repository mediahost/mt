<?php

namespace App\Components\Sign\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Sign;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class SignsGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Sign::getClassName());
		$qb = $repo->createQueryBuilder('g')
				->leftJoin('g.translations', 't')
				->where('t.locale = :lang OR t.locale = :defaultLang')
				->setParameter('lang', $this->translator->getLocale())
				->setParameter('defaultLang', $this->translator->getDefaultLocale());
		$grid->model = new Doctrine($qb, [
			'name' => 't.name',
		]);

		$grid->setDefaultSort([
			'name' => 'ASC',
		]);

		$grid->addColumnNumber('id', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		$grid->addColumnText('name', 'Sign')
				->setColumn('name')
				->setCustomRender(function ($row) {
					return $row->translate($this->translator->getLocale())->name;
				})
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
				->elementPrototype->class[] = 'red';

		$grid->setActionWidth("20%");

		return $grid;
	}

}

interface ISignsGridFactory
{

	/** @return SignsGrid */
	function create();
}
