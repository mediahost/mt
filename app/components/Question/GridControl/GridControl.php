<?php

namespace App\Components\Question;

use App\Components\BaseControl;
use App\Components\Question\GridControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Buyout\Question;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class GridControl extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Question::getClassName());
		$qb = $repo->createQueryBuilder('q')
				->leftJoin('q.translations', 't')
				->where('t.locale = :lang OR t.locale = :defaultLang')
				->setParameter('lang', $this->translator->getLocale())
				->setParameter('defaultLang', $this->translator->getDefaultLocale());
		$grid->model = new Doctrine($qb, [
			'text' => 't.text',
		]);

		$grid->setDefaultSort([
			'text' => 'ASC',
		]);

		$grid->addColumnNumber('id', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		$grid->addColumnText('text', 'Question')
				->setColumn('text')
				->setCustomRender(function ($row) {
					return $row->translate($this->translator->getLocale())->text;
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addActionHref('edit', 'Edit')
				->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setConfirm(function($item) {
							return $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
						})
						->setDisable(function($item) {
							return !$this->presenter->user->isAllowed('question', 'delete');
						})
				->elementPrototype->class[] = 'red';

		$grid->setActionWidth("20%");

		return $grid;
	}

}

interface IGridControlFactory
{

	/** @return GridControl */
	function create();
}
