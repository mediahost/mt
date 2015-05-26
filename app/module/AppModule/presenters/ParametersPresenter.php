<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Parameter;

class ParametersPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('parameters')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$paramRepo = $this->em->getRepository(Parameter::getClassName());
		
		$this->template->parameters = $paramRepo->findAll();
	}

}
