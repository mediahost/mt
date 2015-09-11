<?php

namespace App\Components\Newsletter;

use App\Components\BaseControl;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Facade\NewsletterFacade;
use Nette\Application\UI\Form;
use Nette\Http\Request;
use Nette\Utils\ArrayHash;

class SubscribeControl extends BaseControl
{

	/** @var Request @inject */
	public $request;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator->domain('newsletter.subscribeControl'))
				->setRenderer(new MetronicFormRenderer())
				->getElementPrototype()->class[] = 'ajax';

		$form->addText('email', 'label')
				->addRule(Form::EMAIL)
				->setAttribute('placeholder', $this->translator->translate('placeholder'));

		$form->addSubmit('subscribe', 'submit');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$this->newsletterFacade->subscribe($values->email);
		
		$this->template->success = TRUE;

		if ($this->presenter->isAjax()) {
			$this->redrawControl();
		} else {
			$this->redirect('this');
		}
	}

}

interface ISubscribeControlFactory
{

	/** @return SubscribeControl */
	function create();
}
