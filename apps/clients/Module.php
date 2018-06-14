<?php

namespace Play\Clients;

use Phalcon\DiInterface,
	Phalcon\Loader,
	Phalcon\Mvc\View,
	Phalcon\Mvc\ModuleDefinitionInterface;


class Module implements ModuleDefinitionInterface
{
	/**
	 * Registers an autoloader related to the module
	 *
	 * @param DiInterface $di
	 */
	public function registerAutoloaders(DiInterface $di = null)
	{
		$loader = new Loader();
		$loader->registerNamespaces([
			'Play\Clients\Controllers' => __DIR__ . '/controllers/',
			'Play\Frontend\Controllers' => __DIR__ . '/../frontend/controllers/'
		])->registerDirs([
			__DIR__ . '/../common/classes/',
			__DIR__ . '/../common/models/'
		])->registerClasses([
			'TaskBase' => __DIR__ . '/../tasks/TaskBase.php',
			'PreeventTask' => __DIR__ . '/../tasks/PreeventTask.php'
		])->register();
	}

	/**
	 * Registers services related to the module
	 *
	 * @param DiInterface $di
	 */
	public function registerServices(DiInterface $di)
	{
		/**
		 * Setting up the view component
		 */
		$di['view'] = function () {
			$view = new View();
			$view->setViewsDir(__DIR__ . '/views/');
			return $view;
		};
		$di['url']->setBaseUri('/clients/');

		$di['assets']->collection('script')
			->addJs('/template/js/jquery-2.1.1.js')
			->addJs('/template/js/bootstrap.min.js')
			->addJs('/template/js/plugins/metisMenu/jquery.metisMenu.js')
			->addJs('/template/js/plugins/slimscroll/jquery.slimscroll.min.js')
			->addJs('/template/js/plugins/peity/jquery.peity.min.js')
			->addJs('/template/js/inspinia.js')
			->addJs('/template/js/plugins/pace/pace.min.js')
			->addJs('/template/js/plugins/jquery-ui/jquery-ui.min.js')
			->addJs('/template/js/plugins/toastr/toastr.min.js');
		$di['assets']->collection('style')
			->addCss('/template/css/bootstrap.min.css')
			->addCss('/template/font-awesome/css/font-awesome.css')
			->addCss('/template/css/plugins/toastr/toastr.min.css')
			->addCss('/template/css/animate.css')
			->addCss('/template/css/style.css')
			->addCss('/css/clients/custom.css');
	}
}
