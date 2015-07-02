<?php

namespace App;

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
