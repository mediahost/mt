<?php

namespace App\Components\Page\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Page;
use Grido\DataSources\Doctrine;
use Grido\Grid;
use Nette\Security\User;

class PagesGrid extends BaseControl
{

	/** @var User @inject */
	public $user;

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
			->setParameter('lang', $this->translator->getLocale())
			->setParameter('defaultLang', $this->translator->getDefaultLocale());
		$grid->model = new Doctrine($qb, [
			'name' => 't.name',
		]);

		$grid->setDefaultSort([
			'name' => 'ASC',
		]);

		$grid->addColumnText('name', 'Page')
			->setCustomRender(function ($row) {
				return $row->translate($this->translator->getLocale())->name;
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('comment', 'Comment')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('link', 'Link')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('shop', 'Shop')
			->setSortable()
			->setFilterSelect([NULL => '---'] + $this->shopFacade->getShopPairs());

		$grid->addColumnText('shopVariant', 'Shop variant')
			->setSortable()
			->setFilterSelect([NULL => '---'] + $this->shopFacade->getVariantPairs());

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

interface IPagesGridFactory
{

	/** @return PagesGrid */
	function create();
}
