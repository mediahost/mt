<?php

namespace App\Components\Page\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Page;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use Nette\Application\UI\InvalidLinkException;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class PageEdit extends BaseControl
{

	/** @var Page */
	private $page;

	/** @var User @inject */
	public $user;

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
		$form->setRenderer(new MetronicFormRenderer());

		$form->addText('name', 'Name')
			->setRequired('Name is required')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addText('comment', 'Comment')
			->setAttribute('placeholder', 'Shortly about contain of page')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		if ($this->user->isAllowed('pages', 'editAll')) {
			$form->addText('linkHeadline', 'Link Headline')
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

			$form->addText('linkSubscribe', 'Link Subscribe')
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

			$types = [NULL => '---'] + Page::getTypes();
			$form->addSelect2('type', 'Type', $types)
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;

			$shops = [NULL => '---'] + $this->shopFacade->getShopPairs();
			$form->addSelect2('shop', 'Shop', $shops)
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;

			$variants = [NULL => '---'] + $this->shopFacade->getVariantPairs();
			$form->addSelect2('shopVariant', 'Shop variant', $variants)
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;
		}

		$form->addText('link', 'Link')
			->setAttribute('placeholder', 'Fill if you want to redirect to other page');

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
		try {
			$this->load($values);
			$this->save();
			$this->onAfterSave($this->page);
		} catch (InvalidLinkException $e) {
			$form['link']->addError('This link is invalid');
		}
	}

	private function load(ArrayHash $values)
	{
		$lang = $this->page->isNew() ? $this->translator->getDefaultLocale() : $this->translator->getLocale();
		$translation = $this->page->translateAdd($lang);
		$translation->name = $values->name;
		$translation->html = $values->html;
		if (isset($values->linkHeadline)) {
			$translation->linkHeadline = $values->linkHeadline;
		}
		if (isset($values->linkSubscribe)) {
			$translation->linkSubscribe = $values->linkSubscribe;
		}
		if (isset($values->shop)) {
			$shop = NULL;
			if ($values->shop) {
				$shopRepo = $this->em->getRepository(Shop::getClassName());
				$shop = $shopRepo->find($values->shop);
			}
			$this->page->shop = $shop;
		}
		if (isset($values->shopVariant)) {
			$shopVariant = NULL;
			if ($values->shopVariant) {
				$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());
				$shopVariant = $shopVariantRepo->find($values->shopVariant);
			}
			$this->page->shopVariant = $shopVariant;
		}
		if (isset($values->type)) {
			$this->page->type = $values->type;
		}
		$this->page->comment = $values->comment;
		if ($values->link) {
			$realLink = @$this->presenter->link($values->link);
			if (preg_match('@^#error@', $realLink)) {
				throw new InvalidLinkException();
			}
		}
		$this->page->link = $values->link;
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
			$this->page->setCurrentLocale($this->translator->getLocale());
			$values = [
				'name' => $this->page->name,
				'comment' => $this->page->comment,
				'link' => $this->page->link,
				'html' => $this->page->html,
				'linkHeadline' => $this->page->linkHeadline,
				'linkSubscribe' => $this->page->linkSubscribe,
				'type' => $this->page->rawType,
				'shop' => $this->page->shop ? $this->page->shop->id : NULL,
				'shopVariant' => $this->page->shopVariant ? $this->page->shopVariant->id : NULL,
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
