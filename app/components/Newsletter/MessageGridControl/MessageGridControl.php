<?php

namespace App\Components\Newsletter;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Newsletter\Message;
use App\Model\Facade\LocaleFacade;
use Grido\DataSources\Doctrine;

class MessageGridControl extends BaseControl
{

	const LOCALE_DOMAIN = 'newsletter.admin.newsletter.grid';
	
	/** @var LocaleFacade @inject */
	public $localeFacade;

	/** @return BaseGrid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Message::getClassName());
		$qb = $repo->createQueryBuilder('m')
				->leftJoin('m.group', 'g');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'created' => 'DESC',
		]);

		////////// ID //////////
		$grid->addColumnNumber('id', 'Grido.id')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		////////// Subject //////////
		$grid->addColumnText('subject', self::LOCALE_DOMAIN . '.header.subject')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		////////// Created //////////
		$grid->addColumnDate('created', self::LOCALE_DOMAIN . '.header.created', 'j.n.Y G:i')
				->setSortable()
				->setFilterDate();
		$grid->getColumn('created')->headerPrototype->width = '15%';

		////////// Type //////////
		$grid->addColumnNumber('type', self::LOCALE_DOMAIN . '.header.type')
				->setCustomRender(__DIR__ . DIRECTORY_SEPARATOR . 'type.latte', ['localeDomain' => self::LOCALE_DOMAIN])
				->setFilterSelect([
					NULL => self::LOCALE_DOMAIN . '.all',
					Message::TYPE_USER => self::LOCALE_DOMAIN . '.types.users',
					Message::TYPE_DEALER => self::LOCALE_DOMAIN . '.types.dealers',
		]);
		$grid->getColumn('type')->headerPrototype->width = '10%';

		////////// Locale //////////
		$grid->addColumnText('locale', self::LOCALE_DOMAIN . '.header.locale')
				->setFilterSelect($this->localeFacade->getLocalesToSelect());
		$grid->getColumn('locale')->headerPrototype->width = '10%';

		////////// Actions //////////
		$grid->setActionWidth("15%");
		
		$grid->addActionHref('status', 'run/pause');

		return $grid;
	}

}

interface IMessageGridControlFactory
{

	/** @return MessageGridControl */
	function create();
}
