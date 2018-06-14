<?php

use Dompdf\Dompdf;

define('ASSETS_DIR', __DIR__ . '/../assets/');

class OrderHuntPDF {
	private $dompdf;
	private $fileName;

	public function __construct(OrderHunts $oh, $dateFormat = 'Y-m-d H:i')
	{
		set_time_limit(120);
		ini_set('memory_limit', '256M');
		//$this->Image(ASSETS_DIR . 'logo.png', 14, 9, 40, 12, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

		$dompdf = $this->dompdf = new Dompdf();

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('A4', 'portrait');
		$dompdf->set_option('defaultFont', 'Helvetica');
		$dompdf->set_option('fontHeightRatio', 1.1);


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
		background: #24211f;
		overflow: hidden;
		height: 168px;
	}
	.id_2 > div {
		width: 753px;
		padding: 30px 20px 50px;
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
		height: 303px;
		width: 100%;
		overflow: hidden;
	}
	.pimg > div {
		padding-top: 180px;
	}
	.d2 {
		position: absolute;
		top: 260px;
		height: 25px;
		vertical-align: bottom;
		right: 0;
		z-index: 2;
		padding: 9px 35px;
		text-align: center;
		background: rgba(200, 130, 30, 0.85);
	}
	.pimg .logox {
		display: block;
		position: absolute;
		left: 30px;
		top: 25px;
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
		font: 36px 'Helvetica';
		color: #ffffff;
		line-height: 41px;
	}
	.ft1 {
		font: 20px 'Helvetica';
		color: #ffffff;
		line-height: 23px;
	}
	.ft2 {
		font: bold 14px 'Helvetica';
		color: #f39c1e;
		line-height: 16px;
	}
	.ft3 {
		font: 1px 'Helvetica';
		line-height: 12px;
	}
	.ft4 {
		font: 1px 'Helvetica';
		line-height: 13px;
	}
	.ft5 {
		font: 14px 'Helvetica';
		line-height: 16px;
		color: #000
	}
	.ft6 {
		font: 1px 'Helvetica';
		line-height: 1px;
	}
	.ft7 {
		font: 13px 'Helvetica';
		line-height: 16px;
		color: #000
	}
	.ft8 {
		font: 13px 'Helvetica';
		color: #f39c1e;
		line-height: 16px;
	}
	.ft9 {
		font: 11px 'Helvetica';
		line-height: 14px;
	}
	.ft10 {
		font: bold 15px 'Helvetica';
		color: #f39c1e;
		line-height: 18px;
	}
	.ft11 {
		font: bold 11px 'Helvetica';
		color: #f39c1e;
		line-height: 14px;
		text-decoration: none;
	}
	.ft12 {
		font: bold 10px 'Helvetica';
		line-height: 12px;
	}
	.ft13 {
		font: 1px 'Helvetica';
		line-height: 6px;
	}
	.ft14 {
		font: bold 19px 'Helvetica';
		line-height: 22px;
	}
	.ft15 {
		font: bold 13px 'Helvetica';
		line-height: 16px;
	}
	.ft16 {
		font: 20px 'Helvetica';
		color: #f39c1e;
		line-height: 23px;
	}
	.ft17 {
		font: bold 14px 'Helvetica';
		color: #f39c1e;
		line-height: 16px;
	}
	.ft18 {
		font: bold 11px 'Helvetica';
		color: #ffffff;
		line-height: 14px;
	}
	.ft19 {
		font: 11px 'Helvetica';
		color: #d9d8d3;
		line-height: 14px;
	}
	.ft20 {
		font: 11px 'Helvetica';
		color: #d9d8d3;
		line-height: 13px;
	}
	.ft21 {
		font: bold 8px 'Helvetica';
		color: #ffffff;
		line-height: 5px;
	}
	.ft22 {
		font: 11px 'Helvetica';
		color: #d9d8d3;
		line-height: 15px;
	}
	.ft23 {
		font: bold 11px 'Helvetica';
		color: #ffffff;
		line-height: 15px;
	}
	.ft24 {
		font: bold 11px 'Helvetica';
		color: #ffffff;
		line-height: 16px;
	}
	.ft25 {
		font: 11px 'Helvetica';
		color: #d9d8d3;
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
		white-space: nowrap;
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
		text-align: left;
		padding-left: 0;
		margin-top: 13px;
		margin-bottom: 13px;
		text-align: center
	}
	.p16 {
		text-align: left;
	}
	.p17 {
		text-align: left;
		margin-top: 10px;
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
		margin-top: 10px;
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
		background: #eabf83;
	}
	.td14 {
		padding: 0;
		margin: 0;
		width: 195px;
		vertical-align: bottom;
		background: #dcdbdb;
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
	.tr5 {
		height: 17px;
	}
	.tr6 {
		height: 16px;
	}
	.tr7 {
		height: 20px;
	}
	.tr8 {
		height: 6px;
	}
	.tr9 {
		height: 43px;
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
		margin: 18px 0;
		font: bold 14px 'Helvetica';
		color: #f39c1e;
	}
	.t1 {
		width: 100%;
		font: 11px 'Helvetica';
	}
	.t2 {
		width: 100%;
		font: bold 13px 'Helvetica';
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
		padding: 30px 0;
		text-align: center;
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
	</style>
</head>
<body>
EOF;
for ($i = 0; $i < 50; $i++)
		foreach ($oh->getTeams() as $t => $team) {
			$tname = (is_null($team->name) ? 'Team <span style="color:#000">' . ($t + 1) . '</span>' : $team->name);
			$html .= <<<EOF
	<div class="teamPage" style="page-break-inside: avoid">
		<div class="pimg">
			<img src="{$assetsDir}back.jpg">
			<a href="https://www.strayboots.com" target="_blank" class="logox">
				<img src="{$assetsDir}logo.png" width="189">
			</a>
			<div>
				<p class="p0 ft0">{$order}</p>
				<p class="p1 ft0">{$city} - {$hunt}</p>
				<div class="d2 ft1">{$tname}</div>
			</div>
		</div>
		<div class="id_1" style="page-break-inside: avoid;">
			<table cellpadding=0 cellspacing=0 class="t0">
				<tr>
					<td class="tr0 td0"><p class="p3 ft2">Start:</p></td>
					<td class="tr0 td1"><p class="p3 ft3 spcr">&nbsp;</p></td>
					<td class="tr0 td2"><p class="p4 ft2">Finish:</p></td>
				</tr>
				<tr>
					<td class="tr3 td0"><p class="p3 yw ft5">{$start}</p></td>
					<td class="tr3 td3"><p class="p3 ft6 spcr">&nbsp;</p></td>
					<td class="tr3 td2"><p class="p4 yw ft5">{$finish}</p></td>
				</tr>
			</table>
			<table cellpadding=0 cellspacing=0 class="t1">
				<tr>
					<td class="td41"></td>
					<td class="td41"></td>
					<td class="td41"></td>
					<td class="td41"></td>
					<td class="td41"></td>
					<td class="td41"></td>
					<td class="td41"></td>
				</tr>
				<tr>
					<td class="tr4 td4"><p class="p5 ft8"><img src="{$assetsDir}a_2.png" width="25"></p></td>
					<td class="tr4 td5"><p class="p6 ft9">On your mobile browser, go to:</p></td>
					<td class="tr4 td6"><p class="p7 ft8"><img src="{$assetsDir}a_1.png" width="25"></p></td>
					<td class="tr4 td7"><p class="p3 ft10">Team leader (1 per team)</p></td>
					<td class="tr4 td10"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr4 td8"><p class="p3 ft10">Other team members</p></td>
					<td class="tr4 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr5 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr5 td5"><p class="p6 ft11"><a href="{$config->fullUri}" class="ft11" target="_blank">staging.strayboots.com</a></p></td>
					<td class="tr5 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr5 td7"><p class="p3 ft9">Insert your Activation Code:</p></td>
					<td class="tr5 td10"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr5 td8"><p class="p3 ft9">can use the player code to follow along:</p></td>
					<td class="tr5 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr6 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td5"><p class="p6 ft9">(NO neeed to install an app!)</p></td>
					<td class="tr6 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td7"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td10"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td11"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr6 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr7 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td5"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td13"><p class="nwc ft12"><nobr>{$team->activation_leader}</nobr></p></td>
					<td class="tr7 td10"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr7 td14"><p class="nwc ft12"><nobr>{$team->activation_player}</nobr></p></td>
					<td class="tr7 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr8 td4"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td5"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td6"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td13"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td10"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td14"><p class="p3 ft13">&nbsp;</p></td>
					<td class="tr8 td12"><p class="p3 ft13">&nbsp;</p></td>
				</tr>
				<tr>
					<td class="tr9 td4"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr9 td5"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr9 td6"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr9 td9"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr9 td10"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr9 td11"><p class="p3 ft6">&nbsp;</p></td>
					<td class="tr9 td12"><p class="p3 ft6">&nbsp;</p></td>
				</tr>
			</table>
			<div>
				<p class="p10 ft14">Whoever has the most points WINS!</p>
			</div>
			<table cellpadding=0 cellspacing=0 class="t2">
				<tr>
					<td>
						<img src="{$assetsDir}a_8.png" width="42">
						<p class="nwc ft7"><span class="ft15">Correct</span> answers get</p>
						<p class="nwc ft16">FULL points</p>
					</td>
					<td>
						<img src="{$assetsDir}a_7.png" width="42">
						<p class="nwc ft15">Hints <span class="ft7">get</span></p>
						<p class="nwc ft16">HALF points</p>
					</td>
					<td>
						<img src="{$assetsDir}a_6.png" width="42">
						<p class="nwc ft15">Skips <span class="ft7">get</span></p>
						<p class="nwc ft16">NO points</p>
					</td>
				</tr>
				<tr>
					<td>
						<img src="{$assetsDir}a_5.png" width="42">
						<p class="nwc ft7">Play up the banter with the</p>
						<p class="nwc ft16">Online Chat</p>
					</td>
					<td>
						<img src="{$assetsDir}a_4.png" width="42">
						<p class="nwc ft7">Chart your course with the</p>
						<p class="nwc ft16">Interactive Map</p>
					</td>
					<td>
						<img src="{$assetsDir}a_3.png" width="42">
						<p class="nwc ft7">Check out the competition on the</p>
						<p class="nwc ft16">Live Leaderboard</p>
					</td>
				</tr>
			</table>
		</div>
		<div class="id_2" style="page-break-inside: avoid;">
			<div>
				<div class="id_2_1">
					<p class="p16 ft17">FAQ</p>
					<p class="p17 ft18">Switching Phones</p>
					<p class="p18 ft19">Simply sign in with the LEADER</p>
					<p class="p16 ft20">code on another device to pick</p>
					<p class="p16 ft19">up where you left off.</p>
				</div>
				<div class="id_2_2">
					<p class="p16 ft23">Missing the Answer box? <span class="ft22">Check if someone else logged in with your LEADER code, or</span> <nobr><span class="ft22">log-in</span></nobr> <span class="ft22">again.</span></p>
				</div>
				<div class="id_2_3">
					<p class="p20 ft24">Page not responding or not loading?</p>
					<p class="p16 ft25">Refresh your browser, or give it a few seconds, it will come up.</p>
				</div>
				<div class="id_2_4">
					<p class="p16 ft17">
						Need Help?
						<nobr>877-787-2929</nobr>
					</p>
					<p class="p21 ft22">
						Like us, tag us, tweet us
						<nobr> -- </nobr>
						we want to see your adventures! Make sure to use #strayboots on all of your awesome posts.
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
if (isset($_GET['html'])) {
	echo $html;die;
}
		$dompdf->loadHtml($html);
		$dompdf->render();

		$this->fileName = $config->application->tmpDir . $this->sanitizeName($oh->Order->name) . ' Hunt Instructions‏ ' . $oh->id . '.pdf';
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
		file_put_contents($this->fileName, $dompdf->output());
		return $this->fileName;
	}

	private function sanitizeName($name)
	{
		return mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $name);
	}
}
