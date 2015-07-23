<?php

namespace App\Components\Producer\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\ModelParameter;
use Grido\DataSources\Doctrine;
use Grido\Grid;
use Nette\Utils\Strings;

class ModelParametersGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(ModelParameter::getClassName());
		$qb = $repo->createQueryBuilder('p')
				->leftJoin('p.translations', 't')
				->where('t.locale = :lang OR t.locale = :defaultLang')
				->setParameter('lang', $this->lang)
				->setParameter('defaultLang', $this->translator->getDefaultLocale());
		$grid->model = new Doctrine($qb, [
			'name' => 't.name',
		]);

		$grid->setDefaultSort([
			'name' => 'ASC',
		]);

		$grid->addColumnText('name', 'Name')
				->setCustomRender(function ($row) {
					return $row->translate($this->lang)->name;
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addColumnText('text', 'Text')
				->setCustomRender(function ($row) {
					return Strings::truncate($row->translate($this->lang)->text, 20);
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addActionHref('editParameter', 'Edit')
				->setIcon('fa fa-edit');

		$grid->addActionHref('deleteParameter', 'Delete')
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

interface IModelParametersGridFactory
{

	/** @return ModelParametersGrid */
	function create();
}
