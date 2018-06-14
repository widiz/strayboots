<?php

namespace Play\Admin\Controllers;

class SupportController extends \ControllerBase
{

	public function indexAction()
	{
		if ($this->requireUser())
			return true;
	}

}
