<?php

namespace App\Components\Buyout\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Model\Entity\Buyout\Question;
use Grido\Grid;

class QuestionsGrid extends BaseControl
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

		$grid->addColumnText('text', 'Question')
			->setCustomRender(function ($row) {
				return $row->translate($this->translator->getLocale())->text;
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('type', 'Type')
			->setCustomRender(function (Question $row) {
				return $this->translator->translate($row->formatedType);
			})
		->setFilterSelect(Question::getTypes());

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				return $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
			})
			->setDisable(function ($item) {
				return !$this->presenter->user->isAllowed('question', 'delete');
			})
			->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth("20%");

		return $grid;
	}

}

interface IQuestionsGridFactory
{

	/** @return QuestionsGrid */
	function create();
}
