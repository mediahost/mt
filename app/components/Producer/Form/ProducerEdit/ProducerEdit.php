<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\ModelParameter;
use App\Model\Entity\ParameterPrice;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;

class ProducerEdit extends BaseControl
{

	/** @var Producer|ProducerLine|ProducerModel */
	private $entity;

	/** @var string */
	private $type;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>
	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var bool */
	private $defaultWithVat = TRUE;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addGroup();
		$form->addText('name', 'Name')
				->setRequired('Name is required');

		switch ($this->type) {
			case Producer::ID:
				$form->addUploadImageWithPreview('image', 'Image')
						->setPreview('/foto/200-150/' . ($this->entity->image ? $this->entity->image : 'default.png'), (string) $this->entity)
						->setSize(200, 150)
						->addCondition(Form::FILLED)
						->addRule(Form::IMAGE, 'Image must be in valid image format');
				$form->addWysiHtml('service_html', 'Text for service', 8);
				break;
			case ProducerLine::ID:
				break;
			case ProducerModel::ID:
				$form->addWysiHtml('html', 'Text', 8);
				$form->addUploadImageWithPreview('image', 'Image')
						->setPreview('/foto/200-150/' . ($this->entity->image ? $this->entity->image : 'default.png'), (string) $this->entity)
						->setSize(200, 150)
						->addCondition(Form::FILLED)
						->addRule(Form::IMAGE, 'Image must be in valid image format');

				$parameterRepo = $this->em->getRepository(ModelParameter::getClassName());
				$parameters = $parameterRepo->findAll();

				if (count($parameters)) {
					$form->addSubmit('partSave', 'Save')
									->getControlPrototype()->class[] = 'btn-primary';
					$form->addGroup('Prices');

					$form->addCheckSwitch('with_vat', 'Prices are with VAT', 'YES', 'NO')
							->setDefaultValue($this->defaultWithVat);
					$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
									->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

					$prices = $form->addContainer('prices');
					foreach ($parameters as $parameter) {
						$prices->addText($parameter->id, (string) $parameter)
								->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);
					}
				}
				break;
		}

		$form->addSubmit('save', 'Save')
						->getControlPrototype()->class[] = 'btn-primary';
		if ($this->entity->isNew()) {
			$form->addSubmit('saveAdd', 'Save & Add next');
		}

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();

		$componentsArray = (array) $form->getComponents();
		$isSubmitedByAdd = array_key_exists('saveAdd', $componentsArray) ? $form['saveAdd']->submittedBy : FALSE;
		$this->onAfterSave($this->entity, $this->type, $isSubmitedByAdd);
	}

	private function load(ArrayHash $values)
	{
		$this->entity->name = $values->name;
		switch ($this->type) {
			case Producer::ID:
				$this->loadProducer($values);
				break;
			case ProducerLine::ID:
				break;
			case ProducerModel::ID:
				$this->loadProducerModel($values);
				break;
		}
		return $this;
	}

	private function loadProducer(ArrayHash $values)
	{
		$lang = $this->entity->isNew() ? $this->translator->getDefaultLocale() : $this->translator->getLocale();
		$this->entity->translateAdd($lang)->serviceHtml = $values->service_html;
		$this->entity->mergeNewTranslations();
		if ($values->image->isImage()) {
			$this->entity->image = $values->image;
		}
	}

	private function loadProducerModel(ArrayHash $values)
	{
		$lang = $this->entity->isNew() ? $this->translator->getDefaultLocale() : $this->translator->getLocale();
		$this->entity->translateAdd($lang)->html = $values->html;
		$this->entity->mergeNewTranslations();
		if ($values->image->isImage()) {
			$this->entity->image = $values->image;
		}
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find($values->vat);
		$paramRepo = $this->em->getRepository(ModelParameter::getClassName());
		foreach ($values->prices as $parameterId => $price) {
			$parameter = $paramRepo->find($parameterId);
			if ($parameter && $price > 0) {
				$parameterPrice = $this->entity->getParameterPriceByParameter($parameter, TRUE);
				$parameterPrice->vat = $vat;
				$parameterPrice->setPrice($price, $values->with_vat);
			}
		}
	}

	private function save()
	{
		switch ($this->type) {
			case Producer::ID:
				$repo = $this->em->getRepository(Producer::getClassName());
				break;
			case ProducerLine::ID:
				$repo = $this->em->getRepository(ProducerLine::getClassName());
				break;
			case ProducerModel::ID:
				$repo = $this->em->getRepository(ProducerModel::getClassName());
				break;
			default:
				return $this;
		}
		$repo->save($this->entity);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->entity->name,
		];
		switch ($this->type) {
			case Producer::ID:
				$values['service_html'] = $this->entity->translate($this->translator->getLocale())->serviceHtml;
				$values['image'] = $this->entity->image;
				break;
			case ProducerLine::ID:
				break;
			case ProducerModel::ID:
				$values['html'] = $this->entity->translate($this->translator->getLocale())->html;
				$values['image'] = $this->entity->image;
				$values['with_vat'] = $this->defaultWithVat;
				$values['prices'] = [];
				foreach ($this->entity->parameterPrices as $parameterPrice) {
					$values['prices'][$parameterPrice->parameter->id] = $this->defaultWithVat ? $parameterPrice->price->withVat : $parameterPrice->price->withoutVat;
					$values['vat'] = $parameterPrice->vat->id;
				}
				break;
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->entity || !$this->type) {
			throw new BaseControlException('Use setProducer() before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setProducer($producerOrLineOrModel)
	{
		if ($producerOrLineOrModel instanceof Producer) {
			$this->type = Producer::ID;
		} else if ($producerOrLineOrModel instanceof ProducerLine) {
			$this->type = ProducerLine::ID;
		} else if ($producerOrLineOrModel instanceof ProducerModel) {
			$this->type = ProducerModel::ID;
		}
		$this->entity = $producerOrLineOrModel;
		return $this;
	}

	// </editor-fold>
}

interface IProducerEditFactory
{

	/** @return ProducerEdit */
	function create();
}
