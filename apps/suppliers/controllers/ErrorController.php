<?php

namespace Play\Suppliers\Controllers;

class ErrorController extends \ControllerBase
{

	public function e404Action()
	{
		$this->response->setStatusCode(404, "Not Found");
	}

	public function e500Action()
	{
		$this->response->setStatusCode(500, "Internal Server Error");
	}

}
