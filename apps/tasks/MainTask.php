<?php

class MainTask extends TaskBase
{
	public function mainAction()
	{
		echo "Welcome to Strayboots CLI interface v" . VERSION . PHP_EOL;
		//$this->sendMail(['jenyadco@gmail.com'],'test','','tett %unsubscribe%');
	}
}