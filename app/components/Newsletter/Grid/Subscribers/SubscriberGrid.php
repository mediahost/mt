<?php

namespace App\Components\Newsletter\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\Shop;
use App\Model\Facade\LocaleFacade;
use App\Model\Facade\ShopFacade;
use Grido\DataSources\Doctrine;

class SubscriberGrid extends BaseControl
{

	const LOCALE_DOMAIN = 'newsletter.admin.subscriber.grid';

	/** @var LocaleFacade @inject */
	public $localeFacade;

	/** @return BaseGrid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Subscriber::getClassName());
		$qb = $repo->createQueryBuilder('s');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'subscribed' => 'DESC',
		]);

		////////// ID //////////
		$grid->addColumnNumber('id', 'Grido.id')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		////////// E-mail //////////
		$grid->addColumnText('mail', self::LOCALE_DOMAIN . '.header.email')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		////////// Subscribed //////////
		$grid->addColumnDate('subscribed', self::LOCALE_DOMAIN . '.header.subscribed', 'j.n.Y G:i')
			->setSortable()
			->setFilterDate();
		$grid->getColumn('subscribed')->headerPrototype->width = '15%';

		////////// IP //////////
		$grid->addColumnText('ip', self::LOCALE_DOMAIN . '.header.ip')
			->setFilterText();
		$grid->getColumn('ip')->headerPrototype->width = '15%';

		////////// Type //////////
		$grid->addColumnNumber('type', self::LOCALE_DOMAIN . '.header.type')
			->setCustomRender(__DIR__ . DIRECTORY_SEPARATOR . 'type.latte', ['localeDomain' => self::LOCALE_DOMAIN])
			->setFilterSelect([
				NULL => self::LOCALE_DOMAIN . '.all',
				Subscriber::TYPE_USER => self::LOCALE_DOMAIN . '.types.user',
				Subscriber::TYPE_DEALER => self::LOCALE_DOMAIN . '.types.dealer',
			]);
		$grid->getColumn('type')->headerPrototype->width = '10%';

		////////// Locale //////////
		$grid->addColumnText('locale', self::LOCALE_DOMAIN . '.header.locale')
			->setFilterSelect($this->localeFacade->getLocalesToSelect());
		$grid->getColumn('locale')->headerPrototype->width = '10%';

		////////// Shop //////////
		$grid->addColumnText('shop', self::LOCALE_DOMAIN . '.header.shop')
			->setFilterSelect($this->shopFacade->getPairs());
		$grid->getColumn('shop')->headerPrototype->width = '10%';

		////////// Actions //////////
		$grid->addActionHref('delete', 'Delete', 'delete!')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				return $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
			})
			->setDisable(function ($item) {
				return !$this->presenter->user->isAllowed('question', 'delete');
			});

		$grid->setActionWidth("15%");

		return $grid;
	}

}

interface ISubscriberGridFactory
{

	/** @return SubscriberGrid */
	function create();
}
