<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Tag;

class TagsPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('tags')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$tagRepo = $this->em->getRepository(Tag::getClassName());		
		
		$this->template->tags = $tagRepo->findAll();
	}

}
