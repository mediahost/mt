<?php

namespace App\Components\Page\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Page;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class PagesGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Page::getClassName());
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

		$grid->addColumnText('name', 'Page')
				->setCustomRender(function ($row) {
					return $row->translate($this->lang)->name;
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addColumnText('comment', 'Comment')
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

interface IPagesGridFactory
{

	/** @return PagesGrid */
	function create();
}
