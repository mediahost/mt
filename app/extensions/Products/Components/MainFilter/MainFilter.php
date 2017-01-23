<?php

namespace App\Extensions\Products\Components;

use App\Components\BaseControl;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Parameter;
use App\Model\Entity\Product;
use App\Model\Facade\ProductFacade;
use Tracy\Debugger;

class MainFilter extends BaseControl
{

	/** @var bool */
	private $stored = TRUE;

	/** @var float */
	private $limitPriceMin;

	/** @var float */
	private $limitPriceMax;

	/** @var float */
	private $priceMin;

	/** @var float */
	private $priceMax;

	/** @var string */
	private $currencySymbol;

	/** @var array */
	private $criteria = [];

	/** @var array */
	private $filteredParams = [];

	/** @var array */
	public $onAfterSend = [];

	/** @var ProductFacade @inject */
	public $productFacade;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = [
			!$this->isSendOnChange ?: 'sendOnChange',
			!$this->isAjax ?: 'ajax'
		];

		$form->addCheckbox('onlyAvailable', 'Only Available')
			->setDefaultValue($this->stored);

		$limitPriceMin = floor($this->exchange->change($this->limitPriceMin));
		$limitPriceMax = ceil($this->exchange->change($this->limitPriceMax));

		$fromValue = $this->priceMin ? floor($this->exchange->change($this->priceMin)) : NULL;
		$toValue = $this->priceMax ? ceil($this->exchange->change($this->priceMax)) : NULL;

		if ($limitPriceMax > 0) {
			$form->addText('price', 'Range')
				->setAttribute('data-min', $limitPriceMin)
				->setAttribute('data-max', $limitPriceMax)
				->setAttribute('data-from', $fromValue)
				->setAttribute('data-to', $toValue)
				->setAttribute('data-type', 'double')
				->setAttribute('data-step', '1')
				->setAttribute('data-hasgrid', 'false')
				->setAttribute('data-postfix', ' ' . $this->currencySymbol);
		}

		$defaultValues = [];
		$paramRepo = $this->em->getRepository(Parameter::getClassName());
		$allParams = $paramRepo->findAll();
		foreach ($allParams as $parameter) {
			$parameter->setCurrentLocale($this->translator->getLocale());
			switch ($parameter->type) {
				case Parameter::BOOLEAN:
					$moreItems = $this->productFacade->getParameterValues($parameter, $this->criteria, TRUE);
					if ($moreItems) {
						$form->addCheckbox($parameter->code, $parameter->name);
					}
					break;
				case Parameter::STRING:
					$items = [NULL => '--- Not selected ---'];
					$moreItems = $this->productFacade->getParameterValues($parameter, $this->criteria);
					if ($moreItems) {
						$form->addSelect2($parameter->code, $parameter->name, $items + $moreItems);
					}
					break;
			}
			if (isset($this->filteredParams[$parameter->code])) {
				$defaultValues[$parameter->code] = $this->filteredParams[$parameter->code];
			}
		}
		$form->setDefaults($defaultValues);

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->setStored($values->onlyAvailable);

		$minPrice = $maxPrice = NULL;
		$glue = preg_quote(';');
		if (isset($values->price) && preg_match('/^(\d+)' . $glue . '(\d+)$/', $values->price, $matches)) {
			$minPriceRaw = $matches[1];
			$maxPriceRaw = $matches[2];

			$minPrice = $this->exchange->change($minPriceRaw, $this->exchange->getWeb(), $this->exchange->getDefault());
			$maxPrice = $this->exchange->change($maxPriceRaw, $this->exchange->getWeb(), $this->exchange->getDefault());
			$form['price']
				->setAttribute('data-from', $minPriceRaw)
				->setAttribute('data-to', $maxPriceRaw);
		}

		$params = [];
		foreach (Product::getParameterProperties() as $parameterProperty) {
			if (isset($values->{$parameterProperty->code}) && $values->{$parameterProperty->code}) {
				$params[$parameterProperty->code] = $values->{$parameterProperty->code};
			}
		}

		$this->onAfterSend($this->stored, $minPrice, $maxPrice, $params);
	}

	public function setStored($value = TRUE)
	{
		$this->stored = $value;
		return $this;
	}

	public function setLimitPrices($min, $max)
	{
		$this->limitPriceMin = $min;
		$this->limitPriceMax = $max;
		return $this;
	}

	public function setPrices($min, $max)
	{
		$this->priceMin = $min;
		$this->priceMax = $max;
		return $this;
	}

	public function setCurrencySymbol($symbol)
	{
		$this->currencySymbol = $symbol;
		return $this;
	}

	public function setFilter(array $criteria, array $filteredParams)
	{
		$this->criteria = $criteria;
		$this->filteredParams = $filteredParams;
		return $this;
	}

}

interface IMainFilterFactory
{

	/** @return MainFilter */
	function create();
}
