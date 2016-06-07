<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\ProducerModel;

class AccessoriesPresenter extends BasePresenter
{

	public function actionDefault($id)
	{
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
		if ($id) {
			$modelEntity = $modelRepo->find($id);
		}
		if (isset($modelEntity) && $modelEntity) {
			/* @var $products ProductList */
			$products = $this['products'];
			$products->addFilterAccessoriesFor($modelEntity);

			$this['modelSelector']->setAccessories();
			$this['modelSelector']->setModel($modelEntity);
			$this->getTemplate()->accessoriesFor = $modelEntity;
			$this->setView('../Category/default');
		} else {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Model')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
	}

}
