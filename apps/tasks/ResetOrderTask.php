<?php

class ResetOrderTask extends TaskBase
{
	public function mainAction($args = [])
	{
		echo 'ResetOrder v' . VERSION . PHP_EOL;

		echo 'Env: ' . SBENV . '/' . $this->config->prefix . PHP_EOL;
		$id = isset($args[0]) ? (int)$args[0] : false;
		do {
			while (!(is_numeric($id) && $id > 0)) {
				$id = trim(readline(PHP_EOL . 'Order Hunt ID: '));
				if ($id === '')
					break 2;
			}
			$orderHunt = $id > 0 ? OrderHunts::findFirstById($id) : false;
		} while (!$orderHunt);

		if (strtolower(readline('To continue say "yes": ')) !== 'yes')
			die(PHP_EOL);

		echo ($orderHunt->resetOrderHunt(true, true) ? 'done.' : 'failed.') . PHP_EOL;
	}
}
