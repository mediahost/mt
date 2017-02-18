<?php

namespace App\Router;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Facade\PageFacade;
use App\Model\Facade\ProducerFacade;
use App\Model\Facade\ShopFacade;
use App\Model\Facade\UriFacade;
use Drahak\Restful\Application\Routes\ResourceRoute;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Configurator;

class RouterFactory
{

	const LOCALE_PARAM_NAME = 'locale';

	/** @var string */
	private $defaultLocale;

	/** @var array */
	private $allowedLocales = [];

	/** @var ShopFacade @inject */
	public $shopFacade;

	/** @var PageFacade @inject */
	public $pageFacade;

	/** @var UriFacade @inject */
	public $uriFacade;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	/** @var SettingsStorage @inject */
	public $settings;

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		if (!Configurator::detectDebugMode()) {
			Route::$defaultFlags = Route::SECURED;
		}

		$this->init();

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

		$adminRouter[] = new Route($this->getLocaleParam() . 'app[/<presenter>[/<action>[/<id>]]]', [
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

		$frontRouter[] = new Route('[<locale=sk cs|sk|pl>/]vykup[/<producer>[/<line>]]', [
			'presenter' => 'Buyout',
			'action' => 'default',
		]);
		$frontRouter[] = new Route('en/sell-phone[/<producer>[/<line>]]', [
			'presenter' => 'Buyout',
			'action' => 'default',
			'locale' => 'en',
		]);
		$frontRouter[] = new Route('[<locale=sk cs|sk|pl>/]kupim/<model>', [
			'presenter' => 'Buyout',
			'action' => 'model',
		]);
		$frontRouter[] = new Route('en/sell/<model>', [
			'presenter' => 'Buyout',
			'action' => 'model',
			'locale' => 'en',
		]);
		$frontRouter[] = new Route('[<locale=sk cs|sk|pl>/]servis[/<producer>[/<line>]]', [
			'presenter' => 'Service',
			'action' => 'default',
		]);
		$frontRouter[] = new Route('en/phone-service[/<producer>[/<line>]]', [
			'presenter' => 'Service',
			'action' => 'default',
			'locale' => 'en',
		]);
		$frontRouter[] = new Route('[<locale=sk cs|sk|pl>/]oprava/<model>', [
			'presenter' => 'Service',
			'action' => 'model',
		]);
		$frontRouter[] = new Route('en/fix-service/<model>', [
			'presenter' => 'Service',
			'action' => 'model',
			'locale' => 'en',
		]);
		$frontRouter[] = new Route('vyrobca[/<producer>[/<line>]]', [
			'presenter' => 'Producer',
			'action' => 'default',
			'locale' => 'sk',
		]);
		$frontRouter[] = new Route('cs/vyrobce[/<producer>[/<line>]]', [
			'presenter' => 'Producer',
			'action' => 'default',
			'locale' => 'cs',
		]);
		$frontRouter[] = new Route('pl/producent[/<producer>[/<line>]]', [
			'presenter' => 'Producer',
			'action' => 'default',
			'locale' => 'pl',
		]);
		$frontRouter[] = new Route('en/producer[/<producer>[/<line>]]', [
			'presenter' => 'Producer',
			'action' => 'default',
			'locale' => 'en',
		]);

		$slugs = '[0-9a-z/-]+';

		$frontRouter[] = new FilterRoute($this->getLocaleParam() . 'appropriate/<producer>[/<line>[/<model>]]', [
			'presenter' => 'Category',
			'action' => 'appropriate',
		]);
		$frontRouter[] = new Route($this->getLocaleParam() . 'search[/<text>]', [
			'presenter' => 'Category',
			'action' => 'search',
			'text' => NULL,
			'c' => NULL,
		]);
		$frontRouter[] = new Route($this->getLocaleParam() . 'most-searched', [
			'presenter' => 'MostSearched',
			'action' => 'default',
		]);
		$frontRouter[] = new Route($this->getLocaleParam() . 'producer[/<producer>[/<line>[/<model>]]]', [
			'presenter' => 'Category',
			'action' => 'producer',
			'producer' => NULL,
			'line' => NULL,
			'model' => NULL,
		]);
		$frontRouter[] = new FilterRoute($this->getLocaleParam() . 'c/[<slug ' . $slugs . '>/][page-<products-page \d+>/]<c [0-9]+>.htm[l]', [
			'presenter' => 'Category',
			'action' => 'default',
			'products-page' => 1,
		]);

		$frontRouter[] = $routePage = new FilterRoute($this->getLocaleParam() . 'p/<id ' . $slugs . '>', [
			'presenter' => 'Page',
			'action' => 'default',
		]);

		$frontRouter[] = $routeProduct = new FilterRoute($this->getLocaleParam() . '[<slug [0-9a-z-]+>-]<id [0-9]+>.htm[l]', [
			'presenter' => 'Product',
			'action' => 'default',
		]);
		$frontRouter[] = $routeMain = new FilterRoute($this->getLocaleParam() . '<presenter>[/<action>[/<id>]]', [
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

	private function init()
	{
		$shopSettings = $this->settings->pageConfig->shop;
		$this->defaultLocale = $shopSettings->defaultLocale;
		$this->allowedLocales = (array)$shopSettings->allowedLocales;
		switch ($this->shopFacade->getDomainName()) {
			case 'cz':
				$this->defaultLocale = 'cs';
			case 'sk':
				$this->defaultLocale = 'sk';
			case 'pl':
				$this->defaultLocale = 'pl';
		}
	}

	private function getLocaleParam()
	{
		$allowedLocales = implode('|', $this->allowedLocales);
		return '[<' . self::LOCALE_PARAM_NAME . '=' . $this->defaultLocale . ' ' . $allowedLocales . '>/]';
	}

}
