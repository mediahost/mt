<?php

namespace App\Components\Buyout\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Mail\Messages\Buyout\IOurMessageFactory;
use App\Mail\Messages\Buyout\ITheirMessageFactory;
use App\Model\Entity\Buyout\ModelQuestion as ModelQuestionEntity;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\QuestionFacade;
use Nette\Utils\ArrayHash;

class Request extends BaseControl
{

	/** @var ProducerModel */
	private $model;

	/** @var QuestionFacade @inject */
	public $questionFacade;

	/** @var int */
	private $summary;

	/** @var ModelQuestionEntity[] */
	private $modelQuestions;

	/** @var IOurMessageFactory @inject */
	public $iOurMessageFactory;

	/** @var ITheirMessageFactory @inject */
	public $iTheirMessageFactory;

	/** @var array */
	public $onSend = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicFormRenderer());

		$form->addCheckbox('isNew')
			->setDefaultValue(FALSE);

		$questions = $form->addContainer('questions');
		foreach ($this->model->questions as $qm) {
			$questions->addRadioList($qm->id, $qm->question->text, [
				'y' => $this->translator->translate('buyout.request.input.yes'),
				'n' => $this->translator->translate('buyout.request.input.no'),
			]);
			$questions[$qm->id]->getSeparatorPrototype()->setName(NULL);
		}

		$form->addText('email', 'buyout.request.input.email')
			->setRequired('buyout.request.required.email')
			->addRule(Form::EMAIL);
		$form['email']->getControlPrototype()->class[] = 'form-control';
		$form['email']->getLabelPrototype()->class[] = 'control-label';

		$form->addText('fullname', 'buyout.request.input.fullname')
			->setRequired('buyout.request.required.fullname');
		$form['fullname']->getControlPrototype()->class[] = 'form-control';
		$form['fullname']->getLabelPrototype()->class[] = 'control-label';

		$form->addText('phone', 'buyout.request.input.phone');
		$form['phone']->getControlPrototype()->class[] = 'form-control';
		$form['phone']->getLabelPrototype()->class[] = 'control-label';

		$form->addTextArea('text', 'buyout.request.input.message', NULL, 5)
			->setAttribute('placeholder', 'buyout.request.input.placeholder.message');
		$form['text']->getControlPrototype()->class[] = 'form-control';
		$form['text']->getLabelPrototype()->class[] = 'control-label';

		$form->addSubmit('send', 'buyout.request.input.send');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$this->summary = (int)$this->model->buyoutPrice;

		foreach ($values['questions'] as $id => $question) {
			$qm = $this->model->questions[$id];

			$price = 0;

			if ($question === 'y') {
				$price = (int)$qm->priceA;
			} else if ($question === 'n') {
				$price = (int)$qm->priceB;
			}
			$this->summary += $price;
		}

		if ($this->summary < 0) {
			$this->summary = 0;
		}

		if ($form['send']->isSubmittedBy()) {
			$our = $this->iOurMessageFactory->create();
			$our->addParameter('model', $this->model)
				->addParameter('formData', $values)
				->addParameter('summary', $this->summary);

			$our->setFrom($values->email);
			$our->send();

			$their = $this->iTheirMessageFactory->create();
			$their->addParameter('model', $this->model)
				->addParameter('formData', $values)
				->addParameter('summary', $this->summary);

			$their->addTo($values->email);
			$their->send();

			$this->onSend();
		}

		if ($this->presenter->isAjax()) {
			$this->redrawControl();
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
