<?php

class TimeZoneTransitionType extends \Phalcon\Mvc\Model
{
	public function getSource()
	{
		return 'mysql.time_zone_transition_type';
	}
}