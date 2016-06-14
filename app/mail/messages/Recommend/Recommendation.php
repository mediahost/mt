<?php

namespace App\Mail\Messages\Recommend;

use App\Mail\Messages\BaseMessage;

class Recommendation extends BaseMessage
{

	protected function beforeSend()
	{
		$projectName = $this->settings->pageInfo->projectName;
		$this->setSubject($this->translator->translate('mail.subject.recommend', NULL, ['name' => $projectName]));
		parent::beforeSend();
	}

}

interface IRecommendationFactory
{

	/**
	 * @return Recommendation
	 */
	public function create();
}
