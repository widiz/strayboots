<?php

define('ASSETS_DIR', __DIR__ . '/../assets/');

class OrderHuntPDF extends \TCPDF {
	private $fileName;

	public function Footer()
	{
		$this->Image(ASSETS_DIR . 'logo-w.png', 4, 2, 78.36, 21, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}

	public function __construct(OrderHunts $oh, $dateFormat = 'Y-m-d H:i')
	{
		set_time_limit(120);
		parent::__construct('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$this->SetCreator('Strayboots');
		$this->SetAuthor('Strayboots');
		$this->SetTitle('Strayboots');
		$this->SetSubject('Strayboots');
		$this->SetKeywords('Strayboots');

		// set default monospaced font
		$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$this->SetMargins(0, 0, 0);
		$this->SetHeaderMargin(0);
		$this->SetFooterMargin(0);
		$this->setCellPaddings(0, 0, 0, 0);

		// set auto page breaks
		//$this->SetAutoPageBreak(true, 0);
		$this->SetAutoPageBreak(false);

		// set image scale factor
		$this->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$this->SetFont('helvetica', 'R', 18);
        $this->SetTextColor(255, 255, 255);
		$assetsDir = ASSETS_DIR;

		$order = htmlspecialchars($oh->Order->name);
		$hunt = $oh->Hunt;
		$city = htmlspecialchars($hunt->City->name);
		$hunt = htmlspecialchars($hunt->name);

		$start = is_null($oh->pdf_start) ? date($dateFormat, strtotime($oh->start)) . ' - ' . $city : nl2br(htmlspecialchars($oh->pdf_start));
		$finish = is_null($oh->pdf_finish) ? date($dateFormat, strtotime($oh->finish)) . ' - ' . $city : nl2br(htmlspecialchars($oh->pdf_finish));


		$di = \Phalcon\Di::getDefault();

		$config = $di->get('config');

		$htmlstart = '<!DOCTYPE html>' .
					'<html lang="en">' .
					'<head>' .
						'<meta charset="UTF-8">' .
						'<style>' .
						'* {' .
							'margin: 0;' .
							'padding: 0;' .
						'}' .
						'body, html {' .
							'font-size: 11px;' .
							'font-style: normal;' .
							'font-family: Helvetica, Arial, sans-serif;' .
							'background: #FFF;' .
							'color: #000;' .
						'}' .
						'a {' .
							'color: #ECA147;' .
							'font-style: normal;' .
						'}' .
						'</style>' .
					'</head>';
					//'<body>'
		$htmlend =  //'</body>' .
					'</html>';
		foreach ($oh->getTeams() as $t => $team) {
			$this->AddPage();
			$this->writeHTML(
				$htmlstart .
				'<div style="border-bottom:1px solid #FFF;background-color:#d9d9d9;color:#000">' .
					'<table cellspacing="0" align="right" cellpadding="0" style="font-size:17px">' .
						'<tr valign="bottom">' .
							'<td width="775"></td>' .
							'<td width="275" align="left" style="background-color:#f1cb8b;line-height:1.8;font-size:22px;font-weight:bold;color:#000;" valign="middle">' .
								'&nbsp;&nbsp;' . (is_null($team->name) ? 'Team <span style="color:#000">' . ($t + 1) . '</span>' : $team->name) .
							'</td>' .
							'<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>' .
						'</tr>' .
						'<tr valign="bottom">' .
							'<td style="line-height:2px"></td>' .
						'</tr>' .
						'<tr valign="bottom">' .
							'<td width="320"></td>' .
							'<td width="700" style="text-align:left;font-size:30px;line-height:1.4">' . $order. '<br>' . $city . ' - ' . $hunt . '</td>' .
							/*'<td align="left" style="color:#000" valign="middle">' .
								'<br>' .
								'<br>' .
								'<span style="font-weight:bold">Activation Code:</span><span style="line-height:1.4"><br></span>' . "\n" .
								'<span style="font-weight:bold;color:#ECA147">Leader:</span> ' . $team->activation_leader . '<span style="line-height:1.4"><br></span>' . "\n" .
								'<span style="font-size:12px;line-height:1.5">Player (optional): ' . $team->activation_player . '</span>' .
							'</td>' .*/
						'</tr>' .
					'</table>' .
					'<br><br>' .
				'</div>' .
				'<table cellspacing="0" left="right" cellpadding="10" style="font-size:17px;color:#000">' .
					'<tr valign="bottom">' .
						'<td width="222" style="background-color:#fed899;color:#000;text-align:left;font-size:20px;line-height:1"><br><br><b><i>ON YOUR MARKS...</i></b></td>' .
						'<td width="830" style="text-align:left">' .
							'<table width="100%" cellspacing="0" align="left" cellpadding="0" style="font-size:17px">' .
								'<tr valign="bottom">' .
									'<td width="390" style="text-align:left">' .
										'<table width="100%" cellspacing="0" align="left" cellpadding="10" style="font-size:17px">' .
											'<tr>' .
												'<td>' .
													'<b style="font-size:19px">START:</b><br>' .
													$start .
												'</td>' .
											'</tr>' .
										'</table>' .
									'</td>' .
									'<td width="35" style="text-align:left">' .
										'<img src="' . $assetsDir . 'b4.png" width="8" height="80" align="left">' .
									'</td>' .
									'<td width="390" style="text-align:left">' .
										'<table width="100%" cellspacing="0" align="left" cellpadding="10" style="font-size:17px">' .
											'<tr>' .
												'<td>' .
													'<b style="font-size:19px">END:</b><br>' .
													$finish .
												'</td>' .
											'</tr>' .
										'</table>' .
									'</td>' .
								'</tr>' .
							'</table>' .
						'</td>' .
					'</tr>' .
					'<tr valign="bottom">' .
						'<td width="222" style="background-color:#fed083;color:#000;text-align:left;font-size:20px;line-height:1"><br><br><b><i>GET SET...</i></b></td>' .
						'<td width="830" style="text-align:left">' .
							'<table width="100%" cellspacing="0" align="left" cellpadding="10" style="font-size:17px">' .
								'<tr valign="bottom">' .
									'<td width="55"></td>' .
									'<td width="330" style="text-align:left">' .
										'On your mobile browser, go to:<br>' .
										'<a href="' . $config->fullUri . '" style="color:#e18223;text-decoration:none"><b>' . strtoupper(preg_replace('/^https?:\/\//', '', $config->fullUri)) . '</b></a>' .
										'<br><br><span style="color:#222222">(NO neeed to install an app!)</span>' .
									'</td>' .
									'<td width="63"></td>' .
									'<td width="350" style="text-align:left">' .
										'<b>TEAM <span style="color:#e18223">LEADER</span></b> (<u>1 per team</u>):<br>' .
										'Insert your Activation Code: <span style="color:#e18223"><b>' . $team->activation_leader . '</b></span><br><br>' .
										'Other team members can use the <b>PLAYER</b> code to follow along: <b>' . $team->activation_leader . '</b>' .
									'</td>' .
								'</tr>' .
							'</table>' .
						'</td>' .
					'</tr>' .
					'<tr valign="bottom">' .
						'<td width="222" style="background-color:#ffbd4a;color:#000;text-align:left;font-size:20px;line-height:1"><br><br><b><i>PLAY!</i></b></td>' .
						'<td width="830" style="text-align:left">' .
							'Whoever has the most points WINS!<br>' .
							'<span style="color:#e18223">Correct answers</span> get <b>full</b> points, <span style="color:#e18223">hints</span> get <b>half</b> points, <span style="color:#e18223">skips</span> get <b>no</b> points.<span style="line-height:2"><br></span>' .
							'<table width="100%" cellspacing="0" align="left" cellpadding="0" style="font-size:17px">' .
								'<tr valign="middle">' .
									'<td width="80" style="text-align:left" valign="middle">' .
										'<img src="' . $assetsDir . 'b1.png" width="60" height="60" align="left">' .
									'</td>' .
									'<td width="170" style="text-align:left" valign="middle">' .
										'<span style="line-height:0.5;"><br></span>' .
										'Play up the banter<br>with the <b>chat</b>' .
									'</td>' .
									'<td width="60" style="text-align:left" valign="middle">' .
										'<img src="' . $assetsDir . 'b2.png" width="40" height="60" align="left">' .
									'</td>' .
									'<td width="200" style="text-align:left" valign="middle">' .
										'<span style="line-height:0.5;"><br></span>' .
										'Chart your course with<br>the <b>interactive map</b>' .
									'</td>' .
									'<td width="80" style="text-align:left" valign="middle">' .
										'<img src="' . $assetsDir . 'b3.png" width="60" height="60" align="left">' .
									'</td>' .
									'<td width="200" style="text-align:left" valign="middle">' .
										'<span style="line-height:0.5;"><br></span>' .
										'Check out the competition on the <b>Leaderboard</b>' .
									'</td>' .
								'</tr>' .
							'</table>' .
						'</td>' .
					'</tr>' .
					'<tr valign="bottom">' .
						'<td width="222" style="background-color:#ffaf42;color:#000;text-align:left;font-size:20px;line-height:1"><br><br><i>FAQ</i></td>' .
						'<td width="830" style="text-align:left">' .
							'<table width="100%" cellspacing="0" align="left" cellpadding="10" style="font-size:17px">' .
								'<tr valign="bottom">' .
									'<td width="100%" style="text-align:left">' .
										'NEED HELP??<br>' .
										'NEED HELP??<br>' .
										'NEED HELP??<br>' .
										'NEED HELP??<br>' .
										'NEED HELP??<br>' .
										'NEED HELP??<br>' .
									'</td>' .
								'</tr>' .
							'</table>' .
						'</td>' .
					'</tr>' .
				'</table>' .
				$htmlend,
				true, false, true, false, '');
			
		}
		$this->Circle(74, 92, 6, 0, 360, 'F', null, [38, 34, 98]);
		$this->Text(72, 88, '1');
		$this->Circle(185, 92, 6, 0, 360, 'F', null, [38, 34, 98]);
		$this->Text(183, 88, '2');
		//Close and output PDF document
		$this->fileName = $config->application->tmpDir . $this->sanitizeName($oh->Order->name) . ' Hunt Instructions‏ ' . $oh->id . '.pdf';
	}

	public function downloadPDF($name = '')
	{
		$this->Output(empty($name) ? basename($this->fileName) : $this->sanitizeName($name), 'D');
		exit;
	}

	public function displayPDF($name = '')
	{
		$this->Output(empty($name) ? basename($this->fileName) : $this->sanitizeName($name), 'I');
		exit;
	}

	public function savePDF()
	{
		$this->Output($this->fileName, 'F');
		return $this->fileName;
	}

	private function sanitizeName($name)
	{
		return mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $name);
	}
}
