<?php

use Dompdf\Dompdf;

define('ASSETS_DIR', __DIR__ . '/../assets/');

class OrderHuntPDFNCR extends OrderHuntMailBase {
	private $dompdf;
	private $fileName;

	public function __construct(OrderHunts $oh, $dateFormat = 'Y-m-d H:i', $showLeader = true, $showPlayer = true, $teamId = null, $teamName = null)
	{
		parent::__construct($oh);

		$translate = $this->translate;

		set_time_limit(120);
		ini_set('memory_limit', '256M');
		//$this->Image(ASSETS_DIR . 'logo.png', 14, 9, 40, 12, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

		$dompdf = $this->dompdf = new Dompdf();

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('A4', 'portrait');
		/*$dompdf->set_option('defaultFont', 'Helvetica');
		$dompdf->set_option('fontHeightRatio', 1.1);*/
		$dompdf->set_option('defaultFont', 'OpenSans');
		$dompdf->set_option('fontHeightRatio', 0.78);

		// set document information
		$dompdf->add_info('Creator', 'Strayboots');
		$dompdf->add_info('Author', 'Strayboots');
		$dompdf->add_info('Title', 'Strayboots');
		$dompdf->add_info('Subject', 'Strayboots');
		$dompdf->add_info('Keywords', 'Strayboots');

		$assetsDir = ASSETS_DIR;

		$start = is_null($oh->pdf_start) ? date($dateFormat, strtotime($oh->start)) : nl2br(htmlspecialchars($oh->pdf_start));
		$finish = is_null($oh->pdf_finish) ? date($dateFormat, strtotime($oh->finish)) : nl2br(htmlspecialchars($oh->pdf_finish));

		$order = htmlspecialchars($oh->Order->name);
		$hunt = $oh->Hunt;
		$city = htmlspecialchars($hunt->City->name);
		$hunt = htmlspecialchars($hunt->name);

		$di = \Phalcon\Di::getDefault();

		$config = $di->get('config');

		$hebcss = '';
		$translateArray = [
			'team'		=> $translate->_('Team'),

			'start'		=> $translate->_('Start'),
			'finish'	=> $translate->_('Finish'),
			'goto'		=> $translate->_('On your mobile browser, go to'),

			'leadertxt'		=> $translate->_('Team leader (1 per team)'),
			'othermembers'	=> $translate->_('Other team members'),
			'insert.code'	=> $translate->_('insert your Activation Code'),
			'can.use'		=> $translate->_('can use the member code to follow along'),
			'no.install'	=> $translate->_('(NO neeed to install an app!)'),
			'whoever.wins'	=> $translate->_('Whomever has the most points WINS $100 MARTA or GRTA passes!'),
			'other.prizes'	=> $translate->_('Other prizes will also be awarded for the best photos and trivia answers!'),

			'correct.answer'	=> $translate->_('<span class="ft15">Correct</span> answers get'),
			'full.points'		=> $translate->_('FULL points'),
			'hint.answer'		=> $translate->_('Hints <span class="ft7">get</span>'),
			'half.points'		=> $translate->_('HALF points'),
			'skip.answer'		=> $translate->_('Skips <span class="ft7">get</span>'),
			'no.points'			=> $translate->_('NO points'),

			'play.up'			=> $translate->_('Show us your creativity with'),
			'online.chat'		=> $translate->_('Photo Challenges'),
			'chart.course'		=> $translate->_('Don\'t text while walking'),
			'map'				=> $translate->_('Safety First!'),
			'check.competition'	=> $translate->_('Check out the competition on the'),
			'live.leaderboard'	=> $translate->_('Live Leaderboard'),

			'faq'				=> $translate->_('FAQ'),
			'switch.phone'		=> $translate->_('Switching Phones'),
			'simply.signin'		=> $translate->_('Simply sign in with the LEADER'),
			'code.device'		=> $translate->_('code on another device to pick'),
			'left.off'			=> $translate->_('up where you left off.'),

			'missing.box'		=> $translate->_('Missing the Answer box? <span class="ft22">Check if someone else logged in with your LEADER code, or</span> <nobr><span class="ft22">log in</span></nobr> <span class="ft22">again.</span>'),
			'page.responding'	=> $translate->_('Page not responding or not loading?'),
			'refresh.browser'	=> $translate->_('Refresh your browser, or give it a few seconds. It will come up.'),
			'need.help'			=> $translate->_('Need Help?'),
			'like.us'			=> $translate->_('Like us, tag us, tweet us'),
			'we.want'			=> $translate->_('We want to see your adventures! Make sure to use social media throughout the hunt and tag @NCRCorporation and #NCRlife on all your incredible posts!'),

		];

		/*if ($this->isHeb) {
			$hebcss = <<<EOF
			.p0,.p1,.p2,.p3,.p4,.p5,.p6,.p7,.p8,.p9,.p11,.p12,
			.p13,.p14,.p15,.p16,.p17,.p18,.p19,.p20,.p21,.p22,.p23,.p24,.p25,
			html,body{
				direction: rtl;
				text-align: right;
			}
			@page {
				direction: rtl;
			}
			.id_2_1,.id_2_2,.id_2_3,.id_2_4 {
				float: right;
			}
			.d2 {
				right: auto;
				left: 0
			}
			.p0,.p1 {
				padding-left: 0;
				padding-right: 36px;
			}
			.p4 {
				padding-left: 24px;
			}
			.p5 {
				padding-right: 11px;
			}
			.p6 {
				padding-left: 11px;
			}
			.p7 {
				padding-right: 27px;
			}
			.p19 {
				padding-left: 73px;
			}
			.p20 {
				padding-right: 18px;
			}
			.p21 {
				padding-left: 15px;
				padding-right: 1px;
			}
EOF;
		}*/

		$html = <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title>Strayboots</title>
	<style type="text/css">
	@page teampage {
		size: A4 portrait;
		margin: 0;
	}
	.teamPage {
		page: teampage;
		page-break-inside: avoid;
		page-break-after: always;
		position: relative;
	}
	html, body {
		height: 100%;
		position: relative;
		max-width: 793px
	}
	.hidden {
		display: none;
		visibility: hidden;
	}
	table, html, div, p, body {
		margin: 0;
		padding: 0;
	}
	.id_1 {
		position: relative;
		padding: 0;
		border: 0;
		width: 100%;
		height: 649px;
		overflow: hidden;
		margin: 0
	}
	.id_2 {
		position: absolute;
		bottom: 0;
		width: 100%;
		background: #54B948;
		overflow: hidden;
		height: 150px;
	}
	.id_2 > div {
		width: 753px;
		padding: 22px 20px 40px;
	}
	.id_2_1 {
		float: left;
		padding: 0;
		width: 181px;
		overflow: hidden;
	}
	.id_2_2 {
		float: left;
		margin: 25px 0 0 0;
		padding: 0;
		width: 169px;
		overflow: hidden;
	}
	.id_2_3 {
		float: left;
		margin: 21px 0 0 20px;
		padding: 0;
		width: 167px;
		overflow: hidden;
	}
	.id_2_4 {
		float: left;
		margin: 0 0 0 32px;
		padding: 0;
		width: 184px;
		overflow: hidden;
	}
	.pimg {
		position: relative;
		height: 358px;
		width: 100%;
		overflow: hidden;
	}
	.pimg > div {
		padding-top: 100px;
	}
	.d2 {
		position: absolute;
		top: 315px;
		height: 25px;
		vertical-align: bottom;
		left: 0;
		z-index: 2;
		padding: 9px 35px;
		text-align: center;
		background: rgba(237, 154, 34, 0.85);
	}
	.pimg .logox {
		display: block;
		position: absolute;
		left: 17px;
		top: 15px;
	}
	.pimg > img {
		z-index: -1;
		height: 100%;
		width: 100%;
		position: absolute;
		left: 0;
		top: 0;
	}
	.dclr {
		clear: both;
		float: none;
		height: 1px;
		margin: 0;
		padding: 0;
		overflow: hidden;
	}
	.ft0 {
		font: 36px 'OpenSans';
		color: #F1F2F2;
		line-height: 41px;
	}
	.ft1 {
		font: 20px 'OpenSans';
		color: #F1F2F2;
		line-height: 23px;
	}
	.ft2 {
		font: bold 14px 'OpenSans';
		color: #54b948;
		line-height: 16px;
	}
	.ft3 {
		font: 1px 'OpenSans';
		line-height: 12px;
	}
	.ft4 {
		font: 1px 'OpenSans';
		line-height: 13px;
	}
	.ft5 {
		font: 14px 'OpenSans';
		line-height: 16px;
		color: #000
	}
	.ft6 {
		font: 1px 'OpenSans';
		line-height: 1px;
	}
	.ft7 {
		font: 13px 'OpenSans';
		line-height: 16px;
		color: #000
	}
	.ft8 {
		font: 13px 'OpenSans';
		color: #54b948;
		line-height: 16px;
	}
	.ft9 {
		font: 14px 'OpenSans';
		line-height: 18px;
	}
	.ft10 {
		font: bold 15px 'OpenSans';
		color: #54b948;
		line-height: 18px;
	}
	.ft11 {
		font: bold 16px 'OpenSans';
		color: #54b948;
		line-height: 20px;
		text-decoration: none;
	}
	.ft12 {
		font: bold 16px 'OpenSans';
		line-height: 22px;
	}
	.ft13 {
		font: 1px 'OpenSans';
		line-height: 6px;
	}
	.ft14 {
		font: bold 19px 'RalewayBlack';
		line-height: 20px;
	}
	.ft144 {
		font: normal 15px 'OpenSans';
		line-height: 16px;
	}
	.ft15 {
		font: bold 13px 'OpenSans';
		line-height: 16px;
	}
	.ft16 {
		font: 20px 'OpenSans';
		color: #54b948;
		line-height: 23px;
	}
	.ft17 {
		font: bold 14px 'OpenSans';
		color: #F1F2F2;
		line-height: 16px;
	}
	.ft18 {
		font: bold 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 14px;
	}
	.ft19 {
		font: 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 14px;
	}
	.ft20 {
		font: 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 13px;
	}
	.ft21 {
		font: bold 8px 'OpenSans';
		color: #F1F2F2;
		line-height: 5px;
	}
	.ft22 {
		font: 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 15px;
	}
	.ft23 {
		font: bold 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 15px;
	}
	.ft24 {
		font: bold 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 16px;
	}
	.ft25 {
		font: 11px 'OpenSans';
		color: #F1F2F2;
		line-height: 17px;
	}
	.p0 {
		text-align: left;
		padding-left: 36px;
	}
	.p1 {
		text-align: left;
		padding-left: 36px;
		margin-top: 6px;
		margin-bottom: 0;
	}
	.p3 {
		text-align: left;
	}
	.p4 {
		text-align: left;
		padding-left: 24px;
		white-space: nowrap;
	}
	.p5 {
		text-align: right;
		padding-right: 11px;
		white-space: nowrap;
	}
	.p6 {
		text-align: left;
		padding-left: 11px;
		white-space: nowrap;
	}
	.p7 {
		text-align: right;
		padding-right: 27px;
		white-space: nowrap;
	}
	.nwc {
		text-align: center;
		white-space: nowrap;
	}
	.p10 {
		padding-left: 0;
		margin-top: 10px;
		margin-bottom: 5px;
		text-align: center
	}
	.p10.ft144 {
		margin-top: 0;
		margin-bottom: 13px;
	}
	.p16 {
		text-align: left;
	}
	.p17 {
		text-align: left;
		margin-top: 8px;
		margin-bottom: 0;
	}
	.p18 {
		text-align: left;
		margin-top: 1px;
		margin-bottom: 0;
	}
	.p19 {
		text-align: left;
		padding-left: 73px;
	}
	.p20 {
		text-align: left;
		padding-right: 18px;
	}
	.p21 {
		text-align: left;
		padding-left: 1px;
		padding-right: 15px;
		margin-top: 6px;
		margin-bottom: 0;
	}
	.td0 {
		padding: 0;
		margin: 0;
		width: 350px;
		vertical-align: top;
	}
	.td1 {
		padding: 0;
		margin: 0;
		width: 1px;
		vertical-align: top;
		background: #e4e4e4;
	}
	.td2 {
		padding: 0;
		margin: 0;
		width: 350px;
		vertical-align: top;
	}
	.td3 {
		padding: 0;
		margin: 0;
		width: 1px;
		vertical-align: top;
		background: #e4e4e4;
	}
	.td4 {
		padding: 0;
		margin: 0;
		width: 53px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td5 {
		padding: 0;
		margin: 0;
		width: 184px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td6 {
		padding: 0;
		margin: 0;
		width: 64px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td7 {
		padding: 0;
		margin: 0;
		width: 188px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td8 {
		padding: 0;
		margin: 0;
		width: 195px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.p90 {
		padding: 0;
		margin: 10px 0 6px;
		text-align: center;
		color: #54b948;
		font: bold 19px 'RalewayBlack';
		line-height: 20px;
	}
	.td9 {
		padding: 0;
		margin: 0;
		width: 188px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td10 {
		padding: 0;
		margin: 0;
		width: 60px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td11 {
		padding: 0;
		margin: 0;
		width: 195px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td12 {
		padding: 0;
		margin: 0;
		width: 40px;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td13 {
		padding: 0;
		margin: 0;
		width: 188px;
		vertical-align: bottom;
		background: #e7aa51;
	}
	.td14 {
		padding: 0;
		margin: 0;
		width: 195px;
		vertical-align: bottom;
		background: #E6E6E6;
	}
	.t0 p {
		padding: 0 20px 0 34px;
	}
	.spcr {
		width: 1px;
	}
	.t0 p.spcr {
		padding: 0;
	}
	.p3.spcr {
		height: 19px;
	}
	.tr0 {
		height: 25px;
	}
	.tr1 {
		height: 12px;
	}
	.tr2 {
		height: 13px;
	}
	.tr3 {
		height: 19px;
	}
	.tr4 {
		vertical-align: middle;
	}
	.tr4 img {
		margin-top: 4px;
	}
	.td41 {
		height: 38px;
		padding: 0;
		margin: 0;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.td42 {
		height: 15px;
		padding: 0;
		margin: 0;
		vertical-align: bottom;
		background: #f9f8f8;
	}
	.tr5 {
		height: 17px;
		vertical-align: top;
	}
	.tr6 {
		height: 16px;
	}
	.tr7 {
		height: 22px;
	}
	.tr8 {
		height: 6px;
	}
	.tr9 {
		height: 43px;
	}
	.tr42 {
		height: 15px;
	}
	.tr10 {
		height: 27px;
	}
	.tr11 {
		height: 33px;
	}
	.tr12 {
		height: 32px;
	}
	.t0 {
		width: 100%;
		margin: 0 0 10px;
		font: bold 14px 'OpenSans';
		color: #54b948;
	}
	.t1 {
		width: 100%;
		font: 11px 'OpenSans';
	}
	.t2 {
		width: 100%;
		font: bold 13px 'OpenSans';
	}
	.t2 td:nth-child(2) {
		border-left: 2px solid #f4f4f4;
		border-right: 2px solid #f4f4f4;
	}
	.t2 tr:last-child td {
		border-bottom: 0;
	}
	.t2 td {
		border-top: 2px solid #f4f4f4;
		width: 33.33%;
		padding: 22px 0;
		text-align: center;
	}
	.t2 tr:nth-child(1) td {
		border-top: 0;
	}
	.t2 img + p {
		margin: 12px 0 3px;
	}
	.t2 img + p + p {
		margin: 0;
	}
	.yw {
		white-space: normal;
	}
	{$hebcss}
	</style>
</head>
<body>
EOF;
		$leaderh = $showLeader ? '' : ' hidden';
		$playerh = $showPlayer ? '' : ' hidden';
		$txtUrl = preg_replace('/^https?:\/\//', '', $config->fullUri);
		foreach ((is_object($teamId) ? [$teamId] : $oh->getTeams(is_null($teamId) ? false : true)) as $t => $team) {
			if (is_numeric($teamId) && $teamId != $team->id)
				continue;
			$tname = (is_null($team->name) ? $translateArray['team'] . ' <span style="color:#000">' . (is_null($teamName) ? $t + 1 : $teamName) . '</span>' : $team->name);
			$html .= <<<EOF
	<div class="teamPage" style="page-break-inside: avoid">
		<div class="pimg">
			<img src="{$assetsDir}bgncr.png">
			<div>
				<div class="d2 ft1">{$tname}</div>
			</div>
		</div>
		<p class="p90">
			Your NCR Scavenger Hunt Instructions
		</p>
		<div class="id_1" style="page-break-inside: avoid;">
			<table cellpadding=0 cellspacing=0 class="t0">
				<tr>
					<td class="tr0 td0"><p class="p3 ft2">{$translateArray['start']}:</p></td>
					<td class="tr0 td1"><p class="p3 ft3 spcr">&nbsp;</p></td>
					<td class="tr0 td2"><p class="p4 ft2">{$translateArray['finish']}:</p></td>
				</tr>
				<tr>
					<td class="tr3 td0"><p class="p3 yw ft5">{$start}</p></td>
					<td class="tr3 td3"><p class="p3 ft6 spcr">&nbsp;</p></td>
					<td class="tr3 td2"><p class="p4 yw ft5">{$finish}</p></td>
				</tr>
			</table>
			<table cellpadding=0 cellspacing=0 class="t1">
				<tr>
					<td class="td42"></td>
					<td class="td42"></td>
					<td class="td42"></td>
					<td class="td42{$leaderh}"></td>
					<td class="td42{$leaderh}"></td>
					<td class="td42{$playerh}"></td>
					<td class="td42"></td>
				</tr>
				<tr>
					<td class="tr4 td4"><p class="p5 ft8"><img src="{$assetsDir}a_2ncr.png" width="25"></p></td>
					<td class="tr4 td5"><p class="p6 ft9">{$translateArray['goto']}:</p></td>
					<td class="tr4 td6"><p class="p7 ft8"><img src="{$assetsDir}a_1ncr.png" width="25"></p></td>
					<td class="tr4 td7{$leaderh}"><p class="p3 ft10">{$translateArray['leadertxt']}</p></td>
					<td class="tr4 td10{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr4 td8{$playerh}"><p class="p3 ft10">{$translateArray['othermembers']}</p></td>
					<td class="tr4 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr5 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr5 td5"><p class="p6 ft9"><a href="{$config->fullUri}" class="ft11" target="_blank">{$txtUrl}</a><br>{$translateArray['no.install']}</p></td>
					<td class="tr5 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr5 td7{$leaderh}"><p class="p3 ft9">{$translateArray['insert.code']}:</p></td>
					<td class="tr5 td10{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr5 td8{$playerh}"><p class="p3 ft9">{$translateArray['can.use']}:</p></td>
					<td class="tr5 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr6 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td5"><p class="p6 ft9">&nbsp;</p></td>
					<td class="tr6 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td7{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td10{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td11{$playerh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr7 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td5"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td13{$leaderh}"><p class="nwc ft12"><nobr>{$team->activation_leader}</nobr></p></td>
					<td class="tr7 td10{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td14{$playerh}"><p class="nwc ft12"><nobr>{$team->activation_player}</nobr></p></td>
					<td class="tr7 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr8 td4"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td5"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td6"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td13{$leaderh}"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td10{$leaderh}"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td14{$playerh}"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td12"><p class="p3 ft13">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr42 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr42 td5"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr42 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr42 td9{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr42 td10{$leaderh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr42 td11{$playerh}"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr42 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
			</table>
			<div>
				<p class="p10 ft14">{$translateArray['whoever.wins']}</p>
				<p class="p10 ft144">{$translateArray['other.prizes']}</p>
			</div>
			<table cellpadding=0 cellspacing=0 class="t2">
				<tr>
					<td>
						<img src="{$assetsDir}a_8ncr.png" width="42">
						<p class="nwc ft7">{$translateArray['correct.answer']}</p>
						<p class="nwc ft16">{$translateArray['full.points']}</p>
					</td>
					<td>
						<img src="{$assetsDir}a_7ncr.png" width="42">
						<p class="nwc ft15">{$translateArray['hint.answer']}</p>
						<p class="nwc ft16">{$translateArray['half.points']}</p>
					</td>
					<td>
						<img src="{$assetsDir}a_6ncr.png" width="42">
						<p class="nwc ft15">{$translateArray['skip.answer']}</p>
						<p class="nwc ft16">{$translateArray['no.points']}</p>
					</td>
				</tr>
				<tr>
					<td>
						<img src="{$assetsDir}a_5ncr.png" width="42">
						<p class="nwc ft7">{$translateArray['play.up']}</p>
						<p class="nwc ft16">{$translateArray['online.chat']}</p>
					</td>
					<td>
						<img src="{$assetsDir}a_4ncr.png" width="42">
						<p class="nwc ft7">{$translateArray['chart.course']}</p>
						<p class="nwc ft16">{$translateArray['map']}</p>
					</td>
					<td>
						<img src="{$assetsDir}a_3ncr.png" width="42">
						<p class="nwc ft7">{$translateArray['check.competition']}</p>
						<p class="nwc ft16">{$translateArray['live.leaderboard']}</p>
					</td>
				</tr>
			</table>
		</div>
		<div class="id_2" style="page-break-inside: avoid;">
			<div>
				<div class="id_2_1">
					<p class="p16 ft17">{$translateArray['faq']}</p>
					<p class="p17 ft18">{$translateArray['switch.phone']}</p>
					<p class="p18 ft19">{$translateArray['simply.signin']}</p>
					<p class="p16 ft20">{$translateArray['code.device']}</p>
					<p class="p16 ft19">{$translateArray['left.off']}</p>
				</div>
				<div class="id_2_2">
					<p class="p16 ft23">{$translateArray['missing.box']}</p>
				</div>
				<div class="id_2_3">
					<p class="p20 ft24">{$translateArray['page.responding']}</p>
					<p class="p16 ft25">{$translateArray['refresh.browser']}</p>
				</div>
				<div class="id_2_4">
					<p class="p16 ft17">
						Need Help?
						<nobr>877-787-2929</nobr>
					</p>
					<p class="p21 ft22">
						Make sure to have lots of fun! Tag us @NCRCorporation we want to see your adventures! Make sure to use #NCRlife on all of your incredible posts.
					</p>
				</div>
				<div class="dclr"></div>
			</div>
		</div>
	</div>
EOF;
		}
		$html .= '</body>' .
				'</html>';

		$dompdf->loadHtml($html);
		$dompdf->render();

		$this->fileName = $config->application->tmpDir . 'Your NCR Scavenger Hunt Instructions.pdf';
	}

	public function downloadPDF($name = '')
	{
		$this->dompdf->stream(empty($name) ? basename($this->fileName) : $this->sanitizeName($name), [
			'Attachment' => 1,
			'compress' => 1
		]);
		exit;
	}

	public function displayPDF($name = '')
	{
		$this->dompdf->stream(empty($name) ? basename($this->fileName) : $this->sanitizeName($name), [
			'Attachment' => 0,
			'compress' => 0
		]);
		exit;
	}

	public function savePDF()
	{
		file_put_contents($this->fileName, $this->dompdf->output());
		return $this->fileName;
	}

	public function getOutput()
	{
		return $this->dompdf->output();
	}

	public function getBaseName()
	{
		return basename($this->fileName);
	}

	private function sanitizeName($name)
	{
		return mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $name);
	}
}
