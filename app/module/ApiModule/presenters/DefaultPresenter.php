<?php

namespace App\ApiModule\Presenters;

use Drahak\Restful\Application\BadRequestException;
use Drahak\Restful\IResource;

class DefaultPresenter extends BasePresenter
{

	public function actionDefault()
	{
		$exception = new BadRequestException('Wrong action');
		$this->sendErrorResource($exception, IResource::JSON);
	}

}
