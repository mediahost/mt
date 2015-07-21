<?php

namespace App;

use Drahak\Restful\Application\Routes\ResourceRoute;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

		$router[] = new Route('index.php', 'Front:Default:default', Route::ONE_WAY);

		$router[] = $fotoRouter = new RouteList('Foto');
		$router[] = $apiRouter = new RouteList('Api');
		$router[] = $ajaxRouter = new RouteList('Ajax');
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
			'presenter' => 'Pohoda',
			'action' => 'readOrders',
				], ResourceRoute::GET);

		$apiRouter[] = new ResourceRoute('xml_pohoda/download_stock.php', [
			'presenter' => 'Pohoda',
			'action' => 'readStorageCart'
				], ResourceRoute::GET);

		$apiRouter[] = new ResourceRoute('xml_pohoda/zasoby.php', [
			'presenter' => 'Pohoda',
			'action' => 'createStore'
				], ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('xml_pohoda/zasoby_short.php', [
			'presenter' => 'Pohoda',
			'action' => 'createShortStock'
				], ResourceRoute::POST);

		$apiRouter[] = new ResourceRoute('api/<presenter>/<relation>', [
			'presenter' => 'Default',
			'action' => 'default'
		]);

		// </editor-fold>
		// <editor-fold desc="Ajax">

		$ajaxRouter[] = new Route('ajax/<presenter>/<action>', [
			'presenter' => 'Default',
			'action' => 'default',
		]);

		// </editor-fold>
		// <editor-fold desc="App">

		$adminRouter[] = new Route('app/<presenter>/<action>[/<id>]', [
			'presenter' => 'Dashboard',
			'action' => 'default',
			'id' => NULL,
		]);

		// </editor-fold>
		// <editor-fold desc="Front">

		$frontRouter[] = new Route('search[/<text>]', [
			'presenter' => 'Category',
			'action' => 'search',
			'text' => NULL,
		]);

		$frontRouter[] = new Route('accessories[/<model>]', [
			'presenter' => 'Category',
			'action' => 'accessories',
			'model' => NULL,
		]);

		$frontRouter[] = new Route('producer[/<producer>[/<line>[/<model>]]]', [
			'presenter' => 'Category',
			'action' => 'producer',
			'producer' => NULL,
			'line' => NULL,
			'model' => NULL,
		]);

		$slugs = '[0-9a-z/-]+';
		$sluggablePresenters = [ // alias => Presenter
			'c' => 'Category',
			'p' => 'Product',
			'page' => 'Page',
		];
		foreach ($sluggablePresenters as $alias => $presenter) {
			$frontRouter[] = new Route($alias . '/<url ' . $slugs . '>', [
				'presenter' => $presenter,
				'action' => 'default',
				'url' => NULL,
			]);
		}

		$frontRouter[] = new Route('<presenter>/<action>[/<id>]', [
			'presenter' => 'Homepage',
			'action' => 'default',
			'id' => NULL,
		]);

		// </editor-fold>

		return $router;
	}

}
