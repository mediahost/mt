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
			'code' => 'ASC',
			'name' => 'ASC',
		]);

		$grid->addColumnNumber('code', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('code')->headerPrototype->width = '5%';

		$grid->addColumnNumber('type', 'Type')
				->setCustomRender(function ($row) {
					switch ($row->type) {
						case Parameter::STRING:
							return $this->translator->translate('String');
						case Parameter::INTEGER:
							return $this->translator->translate('Number');
						case Parameter::BOOLEAN:
							return $this->translator->translate('YES/NO');
						default:
							return $row->type;
					}
				});
		$grid->getColumn('type')->headerPrototype->width = '7%';

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
							$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
							return $message;
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
