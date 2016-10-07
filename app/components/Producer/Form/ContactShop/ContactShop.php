<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Mail\Messages\Buyout;
use App\Mail\Messages\Service;
use App\Model\Entity\ProducerModel;

class ContactShop extends BaseControl
{

	/** @var ProducerModel */
	private $model;

	/** @var bool */
	private $service = FALSE;

	/** @var bool */
	private $buyout = FALSE;

	/** @var Buyout\IOurMessageFactory @inject */
	public $iBuyoutOurMessageFactory;

	/** @var Buyout\ITheirMessageFactory @inject */
	public $iBuyoutTheirMessageFactory;

	/** @var Service\IOurMessageFactory @inject */
	public $iServiceOurMessageFactory;

	/** @var Service\ITheirMessageFactory @inject */
	public $iServiceTheirMessageFactory;

	// <editor-fold desc="events">

	/** @var array */
	public $onSend = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator->domain('forms.producer.contactShop'));
		$form->setRenderer(new MetronicFormRenderer());

		$form->addGroup();
		$form->addText('fullname', 'name')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('email', 'mail')
			->setRequired('validator.mail')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('phone', 'phone')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addTextArea('text', 'message', NULL, 6)
			->setRequired('validator.message.fill')
			->addRule(Form::MIN_LENGTH, 'validator.message.min', 10);

		$form->addSubmit('send', 'send')
			->getControlPrototype()->class[] = 'btn-primary';

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		if ($this->buyout) {
			$our = $this->iBuyoutOurMessageFactory->create();
			$their = $this->iBuyoutTheirMessageFactory->create();
		}
		if ($this->service) {
			$our = $this->iServiceOurMessageFactory->create();
			$their = $this->iServiceTheirMessageFactory->create();
		}
		if (isset($our) && isset($their)) {
			$our->setFrom($values->email)
				->addParameter('model', $this->model)
				->addParameter('formData', $values)
				->send();

			$their->addTo($values->email)
				->addParameter('model', $this->model)
				->addParameter('formData', $values)
				->send();
		}
		$this->onSend($this->buyout, $this->service);
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->model) {
			throw new BaseControlException('Use setModel() before render');
		} else if (!$this->buyout && !$this->service) {
			throw new BaseControlException('Use setBuyout(TRUE) or setService(TRUE) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setBuyout($value = TRUE)
	{
		$this->buyout = $value;
		return $this;
	}

	public function setService($value = TRUE)
	{
		$this->service = $value;
		return $this;
	}

	public function setModel($model)
	{
		$this->model = $model;
		return $this;
	}

	// </editor-fold>
}

interface IContactShopFactory
{

	/** @return ContactShop */
	function create();
}
