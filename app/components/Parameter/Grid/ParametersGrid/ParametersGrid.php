<?php

namespace App\Components\Parameter\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Parameter;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class ParametersGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Parameter::getClassName());
		$qb = $repo->createQueryBuilder('p')
				->leftJoin('p.translations', 't')
				->where('t.locale = :lang OR t.locale = :defaultLang')
				->setParameter('lang', $this->lang)
				->setParameter('defaultLang', $this->languageService->defaultLanguage);
		$grid->model = new Doctrine($qb, [
			'name' => 't.name',
		]);

		$grid->setDefaultSort([
			'name' => 'ASC',
		]);

		$grid->addColumnNumber('type', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('type')->headerPrototype->width = '5%';

		$grid->addColumnText('name', 'Parameter')
				->setColumn('name')
				->setCustomRender(function ($row) {
					return $row->translate($this->lang)->name;
				})
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

interface IParametersGridFactory
{

	/** @return ParametersGrid */
	function create();
}
