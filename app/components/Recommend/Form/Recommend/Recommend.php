<?php

namespace App\Components\Recommend\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Mail\Messages\Recommend\IRecommendationFactory;
use App\Model\Entity\Stock;
use Nette\Security\User;

class Recommend extends BaseControl
{

	/** @var User @inject */
	public $user;

	/** @var IRecommendationFactory @inject */
	public $iRecommendFactory;

	/** @var array */
	public $onAfterSend = [];

	/** @var Stock */
	private $stock;

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator->domain('forms.recommend'));
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

		$form->addText('from', 'from', NULL, 255)
			->setRequired('verification.mail.fill')
			->addRule(Form::EMAIL, 'verification.mail.format')
			->setDisabled($this->user->isLoggedIn());

		$form->addText('to', 'to', NULL, 255)
			->setRequired('verification.mail.fill')
			->addRule(Form::EMAIL, 'verification.mail.format');

		$form->addTextArea('message', 'message', NULL, 5)
			->setRequired('verification.message.fill');

		$form->addSubmit('submit', 'send');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$message = $this->iRecommendFactory->create();
		if ($this->user->isLoggedIn() && $this->user->getIdentity()->getMail()) {
			$message->setFrom($this->user->getIdentity()->getMail());
		} else {
			$message->setFrom($values->from);
		}
		$message->addTo($values->to);
		$message->addParameter('text', $values->message);
		$message->addParameter('stock', $this->stock);
		$message->send();

		$this->onAfterSend();
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->user->isLoggedIn()) {
			$values['from'] = $this->user->getIdentity()->getMail();
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->stock) {
			throw new BaseControlException('Use setStock(\App\Model\Entity\Stock) before render');
		}
	}

	public function renderModal()
	{
		$this->setTemplateFile('modal');
		$this->render();
	}

	// <editor-fold desc="setters & getters">

	public function setStock(Stock $stock)
	{
		$this->stock = $stock;
		return $this;
	}

	// </editor-fold>
}

interface IRecommendFactory
{

	/** @return Recommend */
	function create();
}
