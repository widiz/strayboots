<?php

class TimeZoneName extends \Phalcon\Mvc\Model
{
	public function getSource()
	{
		return 'mysql.time_zone_name';
	}
}