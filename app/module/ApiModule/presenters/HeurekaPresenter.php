<?php

namespace App\ApiModule\Presenters;

class HeurekaPresenter extends BasePresenter
{

	public function actionReadProducts($type = 'json')
	{
		$this->resource->title = 'REST API';
		$this->resource->subtitle = '';
		$this->sendResource($this->typeMap[$type]);
	}

	public function actionReadOrders()
	{
		$this->resource->message = 'Hello world';
	}

}
