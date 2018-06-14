<?php

namespace Play\Frontend;

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
			'Play\Frontend\Controllers' => __DIR__ . '/controllers/'
		])->registerDirs([
			//__DIR__ . '/../common/classes/',
			__DIR__ . '/../common/models/'
		])->registerClasses([
			'OrderHuntMailBase' => __DIR__ . '/../common/classes/OrderHuntMailBase.php',
			'OrderHuntPDF' => __DIR__ . '/../common/classes/OrderHuntPDF.php',
			'OrderHuntPDFNCR' => __DIR__ . '/../common/classes/OrderHuntPDFNCR.php'
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
		$di['view'] = function(){
			$view = new View();
			$view->setViewsDir(__DIR__ . '/views/');

			return $view;
		};

		$script = $di['assets']->collection('script')
			->addJs('/template/js/jquery-2.1.1.js')
			//->addJs('/js/app/jquery.mobile.custom.min.js')
			->addJs('/template/js/bootstrap.min.js')
			->addJs('/js/plugins/bootbox.min.js')
			->addJs('/template/js/plugins/pace/pace.min.js')
			//->addJs('/template/js/plugins/jquery.nicescroll/jquery.nicescroll.min.js')
			->addJs('/template/js/plugins/toastr/toastr.min.js')
			->addJs('/js/app/custom.js');
		$style = $di['assets']->collection('style')
			//->addCss('/css/app/bootstrap.custom.min.css')
			->addCss('/template/css/bootstrap.min.css')
			->addCss('/template/font-awesome/css/font-awesome.css')
			->addCss('/template/css/plugins/toastr/toastr.min.css')
			//->addCss('/css/app/jquery.mobile.custom.structure.min.css')
			//->addCss('/css/app/jquery.mobile.custom.theme.min.css')
			->addCss('/css/app/custom.css');
	}
}
