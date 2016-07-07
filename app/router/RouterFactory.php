<?php

namespace App\Router;

use App\Model\Facade\CategoryFacade;
use App\Model\Facade\PageFacade;
use App\Model\Facade\ProducerFacade;
use App\Model\Facade\UriFacade;
use Drahak\Restful\Application\Routes\ResourceRoute;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Configurator;

class RouterFactory
{

	const LOCALE_PARAM_NAME = 'locale';
	const LOCALE_DEFAULT_LANG = 'sk';
	const LOCALE_PARAM = '[<locale=sk cs|sk|en>/]'; // TODO: remove on PHP 5.6
	// TODO: PHP 5.6 can concat strings
//	const LOCALE_PARAM = '[<' . self::LOCALE_PARAM_NAME . '=' . self::LOCALE_DEFAULT_LANG . ' cs|sk|en>/]';

	/** @var PageFacade @inject */
	public $pageFacade;

	/** @var UriFacade @inject */
	public $uriFacade;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		if (!Configurator::detectDebugMode()) {
			Route::$defaultFlags = Route::SECURED;
		}

		$router = new RouteList();

		$router[] = $fotoRouter = new RouteList('Foto');
		$router[] = $apiRouter = new RouteList('Api');
		$router[] = $ajaxRouter = new RouteList('Ajax');
		$router[] = $cronRouter = new RouteList('Cron');
		$router[] = $notificationRouter = new RouteList('Notification');
		$router[] = $adminRouter = new RouteList('App');
		$router[] = $frontRouter = new RouteList('Front');

		// <editor-fold desc="Foto">

		$fotoRouter[] = new Route('foto/[<size \d+\-\d+>/]<name .+>', [
			'presenter' => "Foto",
			'action' => 'default',
			'size' => NULL,
			'name' => NULL,
		]);

		// </editor-fold>
		// <editor-fold desc="Api">

		$apiRouter[] = new ResourceRoute('xml_pohoda/objednavky.php', [
			'presenter' => 'PohodaConnector',
			'action' => 'readOrders',
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('xml_pohoda/download_stock.php', [
			'presenter' => 'PohodaConnector',
			'action' => 'readStorageCart'
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('xml_pohoda/zasoby.php', [
			'presenter' => 'PohodaConnector',
			'action' => 'createStore'
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('xml_pohoda/zasoby_short.php', [
			'presenter' => 'PohodaConnector',
			'action' => 'createShortStock'
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('<locale \w{2}>/export/heureka/products', [
			'presenter' => 'ExportProducts',
			'action' => 'readHeureka'
		], ResourceRoute::GET | ResourceRoute::POST | ResourceRoute::PUT | ResourceRoute::HEAD);

		$apiRouter[] = new ResourceRoute('<locale \w{2}>/export/zbozi/products', [
			'presenter' => 'ExportProducts',
			'action' => 'readZbozi'
		], ResourceRoute::GET | ResourceRoute::POST | ResourceRoute::PUT | ResourceRoute::HEAD);

		$apiRouter[] = new ResourceRoute('<locale \w{2}>/export/dealer/stocks', [
			'presenter' => 'Dealer',
			'action' => 'readStocks'
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('<locale \w{2}>/export/dealer/availability', [
			'presenter' => 'Dealer',
			'action' => 'readAvailability'
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('<locale \w{2}>/export/dealer/categories', [
			'presenter' => 'Dealer',
			'action' => 'readCategories'
		], ResourceRoute::GET | ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('insert/order', [
			'presenter' => 'Dealer',
			'action' => 'createOrder'
		], ResourceRoute::GET | ResourceRoute::POST);

		// </editor-fold>
		// <editor-fold desc="Ajax">

		$ajaxRouter[] = new Route('ajax[/<presenter>[/<action>]]', [
			'presenter' => 'Default',
			'action' => 'default',
		]);

		// </editor-fold>
		// <editor-fold desc="Cron">

		$cronRouter[] = new Route('cron[/<presenter>[/<action>]]', [
			'presenter' => 'Default',
			'action' => 'default',
		]);

		// </editor-fold>
		// <editor-fold desc="Notification">

		$notificationRouter[] = new Route('notification[/<presenter>[/<action>]]', [
			'presenter' => 'Default',
			'action' => 'default',
		]);

		// </editor-fold>
		// <editor-fold desc="App">

		$adminRouter[] = new Route(self::LOCALE_PARAM . 'app[/<presenter>[/<action>[/<id>]]]', [
			'presenter' => 'Dashboard',
			'action' => 'default',
			'id' => NULL,
		]);

		// </editor-fold>
		// <editor-fold desc="Front">

		$frontRouter[] = new Route('install', [
			'presenter' => 'Install',
			'action' => 'default',
		]);

		$frontRouter[] = new Route(self::LOCALE_PARAM . 'search[/<text>]', [
			'presenter' => 'Category',
			'action' => 'search',
			'text' => NULL,
		]);

		$frontRouter[] = new Route(self::LOCALE_PARAM . 'most-searched', [
			'presenter' => 'MostSearched',
			'action' => 'default',
		]);

		$frontRouter[] = new Route(self::LOCALE_PARAM . 'producer[/<producer>[/<line>[/<model>]]]', [
			'presenter' => 'Category',
			'action' => 'producer',
			'producer' => NULL,
			'line' => NULL,
			'model' => NULL,
		]);

		$frontRouter[] = new Route(self::LOCALE_PARAM . 'vykup[/<producer>[/<line>]]', [
			'presenter' => 'Buyout',
			'action' => 'default',
		]);
		$frontRouter[] = new Route(self::LOCALE_PARAM . 'kupim/<model>', [
			'presenter' => 'Buyout',
			'action' => 'model',
		]);
		$frontRouter[] = new Route(self::LOCALE_PARAM . 'servis[/<producer>[/<line>]]', [
			'presenter' => 'Service',
			'action' => 'default',
		]);
		$frontRouter[] = new Route(self::LOCALE_PARAM . 'oprava/<model>', [
			'presenter' => 'Service',
			'action' => 'model',
		]);

		$slugs = '[0-9a-z/-]+';

		$frontRouter[] = $routePage = new FilterRoute(self::LOCALE_PARAM . 'p/<id ' . $slugs . '>', [
			'presenter' => 'Page',
			'action' => 'default',
		]);
		$frontRouter[] = $routeCategory = new FilterRoute(self::LOCALE_PARAM . 'c/[<slug ' . $slugs . '>/]<c>.htm[l]', [
			'presenter' => 'Category',
			'action' => 'default',
		]);
		$frontRouter[] = $routeProduct = new FilterRoute(self::LOCALE_PARAM . '[<slug [0-9a-z-]+>-]<id [0-9a-z]+>.htm[l]', [
			'presenter' => 'Product',
			'action' => 'default',
		]);
		$frontRouter[] = $routeMain = new FilterRoute(self::LOCALE_PARAM . '<presenter>[/<action>[/<id>]]', [
			'presenter' => 'Homepage',
			'action' => 'default',
			'id' => NULL,
		]);

		$routePage->addFilter('id', [$this->pageFacade, 'slugToId'], [$this->pageFacade, 'idToSlug']);
		$routeMain->addFilter('presenter', [$this->uriFacade, 'nameToPresenter'], [$this->uriFacade, 'presenterToName']);
		$routeMain->addFilter('action', [$this->uriFacade, 'nameToAction'], [$this->uriFacade, 'actionToName']);

		// </editor-fold>

		return $router;
	}

}
