<?php

namespace App\Components\Buyout\Form;

use App\Components\BaseControl;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Mail\Messages\IBuyoutOurMessageFactory;
use App\Mail\Messages\IBuyoutTheirMessageFactory;
use App\Model\Entity\Buyout\ModelQuestion;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\QuestionFacade;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class Request extends BaseControl
{

	/** @var ProducerModel */
	private $model;

	/** @var QuestionFacade @inject */
	public $questionFacade;

	/** @var int */
	private $summary;

	/** @var ModelQuestion[] */
	private $modelQuestions;

	/** @var IBuyoutOurMessageFactory @inject */
	public $iBuyoutOurMessageFactory;

	/** @var IBuyoutTheirMessageFactory @inject */
	public $iBuyoutTheirMessageFactory;

	/** @var array */
	public $onSend = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = 'ajax';
		$form->getElementPrototype()->addAttributes(['data-target-loading' => '#request-form-loading']);

		$form->addHidden('modelId');

		$questions = $form->addContainer('questions');

		foreach ($this->model->questions as $qm) {
			$questions->addRadioList($qm->id, $qm->question->text, [
						'y' => $this->translator->translate('Yes') . ' (' . $this->exchange->format($qm->priceA) . ')',
						'n' => $this->translator->translate('No') . ' (' . $this->exchange->format($qm->priceB) . ')',
					])->getControlPrototype()->class[] = 'form-control';
		}

		$form->addText('email', 'E-mail')
						->setRequired()
						->addRule(Form::EMAIL)
						->getControlPrototype()->class[] = 'form-control';

		$form->addText('fullname', 'Full name')
						->setRequired()
						->getControlPrototype()->class[] = 'form-control';

		$form->addSubmit('recalculate', 'Recalculate')
						->setValidationScope(FALSE)
						->setAttribute('style', 'display:none')
						->getControlPrototype()->class[] = 'ajax btn btn-default';

		$form->addSubmit('send', 'Send')
						->getControlPrototype()->class[] = 'btn btn-primary';

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{

		$this->summary = (int) $this->model->buyoutPrice;

		foreach ($values['questions'] as $id => $question) {
			$qm = $this->model->questions[$id];

			$price = 0;

			if ($question === 'y') {
				$price = (int) $qm->priceA;
			} else if ($question === 'n') {
				$price = (int) $qm->priceB;
			}
			$this->summary += $price;

			if ($this->summary < 0) {
				$this->summary = 0;
			}
		}

		if ($form['send']->isSubmittedBy()) {
			$our = $this->iBuyoutOurMessageFactory->create();
			$our->addParameter('model', $this->model)
					->addParameter('formData', $values)
					->addParameter('summary', $this->summary);

			$our->setFrom($values->email);
			$our->send();

			$their = $this->iBuyoutTheirMessageFactory->create();
			$their->addParameter('model', $this->model)
					->addParameter('formData', $values)
					->addParameter('summary', $this->summary);

			$their->addTo($values->email);
			$their->send();

			$this->onSend();
		}

		if ($this->presenter->isAjax()) {
			$this->presenter->redrawControl();
		}
	}

	public function render()
	{
		$this->template->model = $this->model;
		$this->template->summary = $this->summary;
		parent::render();
	}

	public function setModel(ProducerModel $model)
	{
		$this->model = $model;
		$this->model->setCurrentLocale($this->translator->getLocale());
		$this->modelQuestions = $this->model->questions;
		return $this;
	}

}

interface IRequestFactory
{

	/** @return Request */
	function create();
}
