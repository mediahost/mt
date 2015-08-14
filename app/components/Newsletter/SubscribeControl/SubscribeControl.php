<?php

namespace App\Components\Newsletter;

use App\Components\BaseControl;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\User;
use DateTime;
use Kdyby\Doctrine\DuplicateEntryException;
use Nette\Application\UI\Form;
use Nette\Http\Request;
use Nette\Utils\ArrayHash;

class SubscribeControl extends BaseControl
{

	/** @var Request @inject */
	public $request;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
//		$form->getElementPrototype()->class[] = 'ajax';
//		$form->getElementPrototype()->addAttributes(['data-target-loading' => '#request-form-loading']);

		$form->addText('email', 'E-mail')
				->setRequired()
				->addRule(Form::EMAIL);

		$form->addSubmit('subscribe', 'Subscribe');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$this->template->hideForm = TRUE;

		$subscriber = $this->em->getRepository(Subscriber::getClassName())->findOneBy([
			'mail' => $values->email,
			'type' => Subscriber::TYPE_USER,
		]);

		if ($subscriber === NULL) {
			$subscriber = new Subscriber();
		}

		\Nette\Diagnostics\Debugger::barDump($subscriber);

		$subscriber->mail = $values->email;
		$subscriber->ip = $this->request->getRemoteAddress();
		$subscriber->subscribed = new DateTime;
		$subscriber->locale = $this->translator->getLocale();
		$subscriber->type = Subscriber::TYPE_USER;
		$subscriber->unsubscribeToken = 'token'; //Random::generate('8', 'a-z0-9');

		$user = $this->em->getRepository(User::getClassName())->findOneBy(['mail' => $values->email]);

		if ($user !== NULL) {
			$user->setSubscriber($subscriber);
			$subscriber->setUser($user);
			$this->em->persist($user);
		}

		$this->em->persist($subscriber);

		$this->em->beginTransaction();
		
		while (true) {
			try {
				$this->em->flush();
				$this->em->commit();
				break;
			} catch (DuplicateEntryException $e) {
				$this->em->rollback();
				$subscriber->unsubscribeToken = 'token-new'; //Random::generate('8', 'a-z0-9');
//				$this->em->persist($subscriber);
			}
		}

		

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
