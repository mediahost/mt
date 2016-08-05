<?php

namespace App\Components\Newsletter\Form;

use App\Components\BaseControl;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Facade\NewsletterFacade;
use Nette\Application\UI\Form;
use Nette\Http\Request;
use Nette\Utils\ArrayHash;

class Subscribe extends BaseControl
{

	/** @var Request @inject */
	public $request;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator->domain('newsletter.subscribeForm'))
			->setRenderer(new MetronicFormRenderer())
			->getElementPrototype()->class = [
			'ajax',
			'loadingNoOverlay',
			'loadingAnimate',
		];

		$form->addText('mail', 'mail')
			->addRule(Form::EMAIL, 'validator.mail')
			->setAttribute('placeholder', $this->translator->translate('placeholder'));

		$form->addSubmit('subscribe', 'submit');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$this->newsletterFacade->subscribe($values->mail);
		$this->template->success = TRUE;

		if ($this->presenter->isAjax()) {
			$this->redrawControl();
		} else {
			$this->redirect('this');
		}
	}

}

interface ISubscribeFactory
{

	/** @return Subscribe */
	function create();
}
