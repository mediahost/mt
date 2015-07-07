<?php

namespace App\Components\Page\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Page;
use Nette\Utils\ArrayHash;

class PageEdit extends BaseControl
{

	/** @var Page */
	private $page;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$form->addText('name', 'Name')
						->setRequired('Name is required')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addText('comment', 'Comment')
						->setAttribute('placeholder', 'Shortly about contain of page')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addWysiHtml('html', 'Text', 30)
						->setRequired('Content is required')
						->getControlPrototype()->class[] = 'page-html-content';

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->page);
	}

	private function load(ArrayHash $values)
	{
		$lang = $this->page->isNew() ? $this->languageService->defaultLanguage : $this->lang;
		$this->page->translateAdd($lang)->name = $values->name;
		$this->page->translateAdd($lang)->html = $values->html;
		$this->page->comment = $values->comment;
		$this->page->mergeNewTranslations();
		return $this;
	}

	private function save()
	{
		$pageRepo = $this->em->getRepository(Page::getClassName());
		$pageRepo->save($this->page);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if (!$this->page->isNew()) {
			$this->page->setCurrentLocale($this->lang);
			$values = [
				'name' => $this->page->name,
				'comment' => $this->page->comment,
				'html' => $this->page->html,
			];
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->page) {
			throw new BaseControlException('Use setPage(\App\Model\Entity\Page) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setPage(Page $page)
	{
		$this->page = $page;
		return $this;
	}

	// </editor-fold>
}

interface IPageEditFactory
{

	/** @return PageEdit */
	function create();
}
