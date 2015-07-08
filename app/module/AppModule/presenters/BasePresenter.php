<?php

namespace App\AppModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;

abstract class BasePresenter extends BaseBasePresenter {

	/**
	 * @return \IPub\AssetsLoader\Components\CssLoader
	 */
	protected function createComponentCssAdmin() {
		return $this->assetsLoader->createCssLoader('admin');
	}

	/**
	 * @return \IPub\AssetsLoader\Components\JsLoader
	 */
	protected function createComponentJsAdmin() {
		return $this->assetsLoader->createJsLoader('admin');
	}

}
