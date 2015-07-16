<?php

namespace App\Components\Product\Form;

use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class StockSeo extends StockBase
{

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$product = $this->stock->product;
		$product->setCurrentLocale($this->lang);

		$form->addText('url', 'Url')
				->setAttribute('placeholder', $product->slug)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Url must have %count% character at least', 5);
		$form->addText('title', 'Title')
				->setAttribute('placeholder', $product->name)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Title must have %count% character at least', 5);
		$form->addTextArea('keywords', 'Keywords');
		$form->addTextArea('description', 'Description')
				->setAttribute('placeholder', $product->perex);

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->stock);
	}

	private function load(ArrayHash $values)
	{
		$this->stock->product->setCurrentLocale($this->lang);
		$expectedSlug = Strings::webalize($this->stock->product->name);
		if ($values->url && $values->url !== $expectedSlug) {
			$this->stock->product->translateAdd($this->lang)->slug = $values->url;
		}
		$this->stock->product->translateAdd($this->lang)->seo->name = $values->title;
		$this->stock->product->translateAdd($this->lang)->seo->keywords = $values->keywords;
		$this->stock->product->translateAdd($this->lang)->seo->description = $values->description;
		$this->stock->product->mergeNewTranslations();

		return $this;
	}

	private function save()
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stockRepo->save($this->stock);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->stock->product) {
			$this->stock->product->setCurrentLocale($this->lang);
			$values += [
				'url' => $this->stock->product->slug,
			];
			$expectedSlug = Strings::webalize($this->stock->product->name);
			if ($this->stock->product->slug === $expectedSlug) {
				$values['url'] = NULL;
			}
		}
		if ($this->stock->product && $this->stock->product->seo) {
			$values += [
				'title' => $this->stock->product->seo->name,
				'keywords' => $this->stock->product->seo->keywords,
				'description' => $this->stock->product->seo->description,
			];
		}
		return $values;
	}

}

interface IStockSeoFactory
{

	/** @return StockSeo */
	function create();
}
