<?php

namespace App\Components\Product\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Stock;
use Grido\DataSources\Doctrine;
use Grido\Grid;

class StocksGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Stock::getClassName());
		$qb = $repo->createQueryBuilder('s')
				->select('s, p')
				->leftJoin('s.product', 'p')
				->leftJoin('s.price', 'pr')
				->leftJoin('p.translations', 't')
				->where('t.locale = :lang OR t.locale = :defaultLang')
				->setParameter('lang', $this->lang)
				->setParameter('defaultLang', $this->languageService->defaultLanguage);
		$grid->model = new Doctrine($qb, [
			'product' => 'p',
			'product.name' => 't.name',
			'price.withoutVat' => 'pr.value',
		]);

		$grid->setDefaultSort([
			'quantity' => 'DESC',
		]);

		$grid->addColumnNumber('id', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';
		
		$grid->addColumnText('title', 'Product title')
				->setColumn('product.name')
				->setSortable()
				->setFilterText()
				->setSuggestion();
		
		$grid->addColumnNumber('purchasePrice', 'Purchase price')
				->setNumberFormat(0, ',', ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('purchasePrice')->headerPrototype->style = 'width:130px';
		$grid->getColumn('purchasePrice')->cellPrototype->style = 'text-align: right';
		
		$grid->addColumnNumber('price', 'Price')
				->setNumberFormat(2, ',', ' ')
				->setColumn('price.withoutVat')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('price')->headerPrototype->style = 'width:110px';
		$grid->getColumn('price')->cellPrototype->style = 'text-align: right';
		
		$grid->addColumnNumber('quantity', 'Store')
				->setNumberFormat(0, NULL, ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('quantity')->headerPrototype->style = 'width:100px';
		$grid->getColumn('quantity')->cellPrototype->style = 'text-align: right';
		
		$grid->addColumnNumber('inStore', 'E-shop')
				->setNumberFormat(0, NULL, ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('inStore')->headerPrototype->style = 'width:100px';
		$grid->getColumn('inStore')->cellPrototype->style = 'text-align: right';
		
		$grid->addColumnNumber('lock', 'Locked')
				->setNumberFormat(0, NULL, ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('lock')->headerPrototype->style = 'width:90px';
		$grid->getColumn('lock')->cellPrototype->style = 'text-align: right';
		
		$grid->addColumnBoolean('active', 'Public')
				->setSortable()
				->setFilterSelect([1 => 'YES', 0 => 'NO']);
		$grid->getColumn('active')->headerPrototype->style = 'width:95px';
		
		$grid->addActionHref('view', 'View on web', ':Front:Product:viewById')
						->setIcon('fa fa-eye');

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

		$grid->setExport('stocks');

		return $grid;
	}

}

interface IStocksGridFactory
{

	/** @return StocksGrid */
	function create();
}
