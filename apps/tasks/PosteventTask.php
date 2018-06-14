<?php

class PosteventTask extends TaskBase
{
	public function mainAction()
	{
		//$this->oldAction();
		//echo PHP_EOL;
		$this->newAction();
		$this->playersAction();
	}

	/*public function oldAction()
	{
		echo 'Post Event v' . VERSION . PHP_EOL;
		echo 'Querying...';
		$builder = new \Phalcon\Mvc\Model\Query\Builder([
			'models'     => ['OrderHunts'],
			'conditions' => 'OrderHunts.finish > \'' . date('Y-m-d H:i:s', strtotime('-4 hours')) . '\' AND OrderHunts.finish <= \'' . date('Y-m-d H:i:s') . '\' AND OrderHunts.flags & 4 = 0 AND post.id IS NULL AND t.id IS NOT NULL'
		]);
		$builder->leftJoin('OrderHuntsPost', 'post.order_hunt_id = OrderHunts.id AND post.identifier = ' . OrderHuntsPost::PostEventEmail, 'post');
		$builder->leftJoin('Teams', 't.order_hunt_id = OrderHunts.id AND t.activation IS NOT NULL', 't');
		$builder->groupBy('OrderHunts.id');
		$results = $builder->getQuery()->execute();
		echo 'done' . PHP_EOL;
		$sm = [$this, 'sendMail'];
		foreach ($results as $orderHunt) {
			echo 'Processing order hunt #' . $orderHunt->id;
			$pe = new OrderHuntPostEvent($orderHunt);
			echo '.';
			if ($orderHunt->isMultiHunt()) {
				echo '*';
				$id = (int)$this->db->fetchColumn("SELECT id FROM order_hunts WHERE order_id = {$orderHunt->order_id} AND flags & 4 = 0 AND flags & 8 = 8 ORDER BY finish DESC LIMIT 1");
				echo '.';
				if ($id === 0) {
					echo 'failed to find multi hunt last hunt' . PHP_EOL;
					continue;
				} else if ($id != $orderHunt->id) {
					echo 'multihunt - skipping' . PHP_EOL;
					continue;
				}
				echo '.';
			}
			$post = new OrderHuntsPost();
			$post->order_hunt_id = $orderHunt->id;
			$post->identifier = OrderHuntsPost::PostEventEmail;
			try {
				if ($post->save()) {
					echo '.';
					$mail = $pe->send($sm);
					echo '.';
					if ($mail === true) {
						echo 'done' . PHP_EOL;
					} else {
						echo 'failed' . PHP_EOL;
						var_dump($mail);
						if (!$post->delete())
							var_dump($post->getMessages());
					}
				} else {
					echo 'failed' . PHP_EOL;
					var_dump($post->getMessages());
				}
			} catch (\Exception $e) {
				echo 'exception' . PHP_EOL;
				var_dump($e->getMessage());
			}
		}
	}*/

	public function newAction()
	{
		echo 'New Post Event v' . VERSION . PHP_EOL;
		echo 'Querying...';
		$builder = new \Phalcon\Mvc\Model\Query\Builder([
			'models'     => ['OrderHunts'],
			'conditions' => 'OrderHunts.finish > \'' . date('Y-m-d H:i:s', strtotime('-4 hours')) . '\' AND OrderHunts.finish <= \'' . date('Y-m-d H:i:s') . '\' AND OrderHunts.flags & 4 = 0'// AND post.id IS NULL AND t.id IS NOT NULL'
		]);
		$builder->leftJoin('OrderHuntsPost', 'post.order_hunt_id = OrderHunts.id AND post.identifier = ' . OrderHuntsPost::NewPostEventEmail, 'post');
		$builder->leftJoin('Teams', 't.order_hunt_id = OrderHunts.id AND t.activation IS NOT NULL', 't');
		$builder->groupBy('OrderHunts.id');
		$results = $builder->getQuery()->execute();
		echo 'done' . PHP_EOL;
		$sm = [$this, 'sendMail'];
		foreach ($results as $orderHunt) {
			echo 'Processing order hunt #' . $orderHunt->id;
			$pe = new NewOrderHuntPostEvent($orderHunt, false, true);
			echo '.';
			if ($orderHunt->isMultiHunt()) {
				echo '*';
				$id = (int)$this->db->fetchColumn("SELECT id FROM order_hunts WHERE order_id = {$orderHunt->order_id} AND flags & 4 = 0 AND flags & 8 = 8 ORDER BY finish DESC LIMIT 1");
				echo '.';
				if ($id === 0) {
					echo 'failed to find multi hunt last hunt' . PHP_EOL;
					continue;
				} else if ($id != $orderHunt->id) {
					echo 'multihunt - skipping' . PHP_EOL;
					continue;
				}
				echo '.';
			}
			$post = new OrderHuntsPost();
			$post->order_hunt_id = $orderHunt->id;
			$post->identifier = OrderHuntsPost::NewPostEventEmail;
			try {
				if ($post->save()) {
					echo '.';
					$mail = $pe->send($sm);
					echo '.';
					if ($mail === true) {
						echo 'done' . PHP_EOL;
					} else {
						echo 'failed' . PHP_EOL;
						var_dump($mail);
						if (!$post->delete())
							var_dump($post->getMessages());
					}
				} else {
					echo 'failed' . PHP_EOL;
					var_dump($post->getMessages());
				}
			} catch (\Exception $e) {
				echo 'exception' . PHP_EOL;
				var_dump($e->getMessage());
			}
		}
	}

	public function playersAction()
	{
		echo 'New Post Event Players v' . VERSION . PHP_EOL;
		echo 'Querying...';
		$builder = new \Phalcon\Mvc\Model\Query\Builder([
			'models'     => ['OrderHunts'],
			'conditions' => 'OrderHunts.finish > \'' . date('Y-m-d H:i:s', strtotime('-4 hours')) . '\' AND OrderHunts.finish <= \'' . date('Y-m-d H:i:s') . '\' AND OrderHunts.flags & 4 = 0 AND post.id IS NULL AND t.id IS NOT NULL'
		]);
		$builder->leftJoin('OrderHuntsPost', 'post.order_hunt_id = OrderHunts.id AND post.identifier = ' . OrderHuntsPost::NewPostEventEmailPlayers, 'post');
		$builder->leftJoin('Teams', 't.order_hunt_id = OrderHunts.id AND t.activation IS NOT NULL', 't');
		$builder->groupBy('OrderHunts.id');
		$results = $builder->getQuery()->execute();
		echo 'done' . PHP_EOL;
		$sm = [$this, 'sendMail'];
		foreach ($results as $orderHunt) {
			echo 'Processing order hunt #' . $orderHunt->id;
			$pe = new NewOrderHuntPostEvent($orderHunt, true, false);
			echo '.';
			if ($orderHunt->isMultiHunt()) {
				echo '*';
				$id = (int)$this->db->fetchColumn("SELECT id FROM order_hunts WHERE order_id = {$orderHunt->order_id} AND flags & 4 = 0 AND flags & 8 = 8 ORDER BY finish DESC LIMIT 1");
				echo '.';
				if ($id === 0) {
					echo 'failed to find multi hunt last hunt' . PHP_EOL;
					continue;
				} else if ($id != $orderHunt->id) {
					echo 'multihunt - skipping' . PHP_EOL;
					continue;
				}
				echo '.';
			}
			$post = new OrderHuntsPost();
			$post->order_hunt_id = $orderHunt->id;
			$post->identifier = OrderHuntsPost::NewPostEventEmailPlayers;
			try {
				if ($post->save()) {
					echo '.';
					$mail = $pe->send($sm);
					echo '.';
					if ($mail === true) {
						echo 'done' . PHP_EOL;
					} else {
						echo 'failed' . PHP_EOL;
						var_dump($mail);
						if (!$post->delete())
							var_dump($post->getMessages());
					}
				} else {
					echo 'failed' . PHP_EOL;
					var_dump($post->getMessages());
				}
			} catch (\Exception $e) {
				echo 'exception' . PHP_EOL;
				var_dump($e->getMessage());
			}
		}
	}
}
