<?php

namespace Test;

use LogicException;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Nette\Application\IResponse;
use Nette\Application\Request as AppRequest;
use Nette\Application\UI\Presenter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use ReflectionProperty;
use Tracy\Debugger;

abstract class PresenterTestCase extends DbTestCase
{

	const GET = 'GET';
	const POST = 'POST';

	/** @var Presenter */
	private $presenter;

	/** @var UrlScript */
	private $fakeUrl;

	protected function openPresenter($name)
	{
		$container = $this->getContainer();
		$this->insertFakeUrl();

		/** @var IPresenterFactory $presenterFactory */
		$presenterFactory = $container->getByType(IPresenterFactory::class);
		$class = $presenterFactory->getPresenterClass($name);

		if (!class_exists($overriddenPresenter = 'MyPresenterTests\\' . $class)) {
			$classPos = strrpos($class, '\\');
			eval('namespace MyPresenterTests\\' . substr($class, 0, $classPos) . '; class ' . substr($class, $classPos + 1) . ' extends \\' . $class . ' { '
					. 'protected function startup() { if ($this->getParameter("__terminate") == TRUE) { $this->terminate(); } parent::startup(); } '
					. 'public static function getReflection() { return parent::getReflection()->getParentClass(); } '
					. '}');
		}

		$this->presenter = $container->createInstance($overriddenPresenter);
		$container->callInjects($this->presenter);

		$app = $this->getService(Application::class);
		$appRefl = new ReflectionProperty($app, 'presenter');
		$appRefl->setAccessible(TRUE);
		$appRefl->setValue($app, $this->presenter);

		$this->presenter->autoCanonicalize = FALSE;
		$this->presenter->run(new AppRequest($name, self::GET, ['action' => 'default', '__terminate' => TRUE]));
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 * @return IResponse
	 */
	protected function runPresenterAction($action, $method = self::GET, $params = [], $post = [])
	{
		if (!$this->presenter) {
			throw new LogicException('You have to open the presenter using $this->openPresenter($name); before calling actions');
		}

		$request = new AppRequest($this->presenter->getName(), $method, ['action' => $action] + $params, $post);

		return $this->presenter->run($request);
	}

	protected function runPresenterActionGet($action, $params = [])
	{
		return $this->runPresenterAction($action, self::GET, $params);
	}

	protected function runPresenterActionPost($action, $params = [])
	{
		return $this->runPresenterAction($action, self::POST, [], $params);
	}

	/**
	 * @param $action
	 * @param $signal
	 * @param array $params
	 * @param array $post
	 * @return IResponse
	 */
	protected function runPresenterSignal($action, $signal, $params = [], $post = [])
	{
		return $this->runPresenterAction($action, $post ? self::POST : self::GET, ['do' => $signal] + $params, $post);
	}

	/** insert fake HTTP Request for Presenter - for presenter->link() etc. */
	private function insertFakeUrl()
	{
		$container = $this->getContainer();
		$params = $container->getParameters();
		$url = isset($params['console']['url']) ? $params['console']['url'] : 'localhost';
		$this->fakeUrl = new UrlScript($url);

		$serviceName = 'httpRequest';
		$container->removeService($serviceName);
		$container->addService($serviceName, new HttpRequest($this->fakeUrl, NULL, [], [], [], [], PHP_SAPI, '127.0.0.1', '127.0.0.1'));
	}

	private function getService($type)
	{
		$container = $this->getContainer();
		if ($object = $container->getByType($type, FALSE)) {
			return $object;
		}

		return $container->createInstance($type);
	}

}
