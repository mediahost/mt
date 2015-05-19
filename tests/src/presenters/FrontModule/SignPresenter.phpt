<?php

namespace Test\Presenters\FrontModule;

use Nette\Application\Responses\RedirectResponse;
use Test\Presenters\BasePresenter;
use Tester\Assert;
use Tester\DomQuery;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: SignPresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class SignPresenterTest extends BasePresenter
{

	protected function setUp()
	{
		parent::setUp();
		$this->initSystem();
		$this->openPresenter('Front:Sign');
	}

	public function testRenderIn()
	{
		$response = $this->runPresenterActionGet('in');

		$html = (string) $response->getSource();
		$dom = DomQuery::fromHtml($html);

		Assert::true($dom->has('html'));
		Assert::true($dom->has('head'));
		Assert::true($dom->has('title'));
		Assert::true($dom->has('body'));

		$form = $dom->find('form#frm-signIn-form');
		Assert::count(1, $form);
		
		$mail = $dom->find('input#frm-signIn-form-mail[type=text]');
		Assert::count(1, $mail);
		
		$password = $dom->find('input#frm-signIn-form-password[type=password]');
		Assert::count(1, $password);
		
		$remember = $dom->find('input#frm-signIn-form-remember[type=checkbox]');
		Assert::count(1, $remember);
		
		$button = $dom->find('button[type=submit][name=signIn]');
		Assert::count(1, $button);
	}

	public function testRenderInLogged()
	{
		$this->loginAdmin();
		$response = $this->runPresenterActionGet('in');
		Assert::type(RedirectResponse::class, $response);
		$this->logout();
	}

}

$test = new SignPresenterTest($container);
$test->run();
