<?php

use Phalcon\Db;

class BCOrderHuntPostEvent extends OrderHuntMailBase {

	public function __construct(OrderHunts $orderHunt)
	{
		parent::__construct($orderHunt);

		$data = EventEmails::findFirstByEmailId(EventEmails::BCOrderHuntPostEvent);

		$translate = $this->translate;

		$this->client = $orderHunt->Order->Client;
		$this->title = $data ? $data->title : $translate->_('Thank you for using Strayboots!');

		$this->text = str_replace('%unsubscribe%', '', $data ? $data->text : $translate->_('BCOrderHuntPostEventText'));
		$this->html = str_replace('%unsubscribe%', '', $data ? $data->html : $translate->_('BCOrderHuntPostEventHTML'));

	}
}
