<?php

define('ASSETS_DIR', __DIR__ . '/../assets/');

class OrderHuntPDF extends \TCPDF {
	private $fileName;

	//Page header
	public function Header()
	{
		$this->Image(ASSETS_DIR . 'logo.png', 14, 9, 40, 12, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}

	// Page footer
	public function Footer()
	{
	}

	public function __construct(OrderHunts $oh, $dateFormat = 'Y-m-d H:i')
	{
		set_time_limit(120);
		parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$this->SetCreator('Strayboots');
		$this->SetAuthor('Strayboots');
		$this->SetTitle('Strayboots');
		$this->SetSubject('Strayboots');
		$this->SetKeywords('Strayboots');

		// set default monospaced font
		$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$this->SetMargins(PDF_MARGIN_LEFT, 0, PDF_MARGIN_RIGHT);
		$this->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		//$this->SetAutoPageBreak(true, 0);
		$this->SetAutoPageBreak(false);

		// set image scale factor
		$this->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$this->SetFont('helvetica', 'R', 12);
		$assetsDir = ASSETS_DIR;

		$start = is_null($oh->pdf_start) ? date($dateFormat, strtotime($oh->start)) : nl2br(htmlspecialchars($oh->pdf_start));
		$finish = is_null($oh->pdf_finish) ? date($dateFormat, strtotime($oh->finish)) : nl2br(htmlspecialchars($oh->pdf_finish));

		$order = htmlspecialchars($oh->Order->name);
		$hunt = $oh->Hunt;
		$city = htmlspecialchars($hunt->City->name);
		$hunt = htmlspecialchars($hunt->name);

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
					'}' .
					'a {' .
						'color: #ECA147;' .
						'font-style: normal;' .
					'}' .
					'.section__title {' .
						'color: #ECA147;' .
						'font-size: 1.5em;' .
						'font-weight: bold;' .
						'padding-bottom: 25px;' .
					'}' .
					'.section__title span {' .
						'color: #000;' .
					'}' .
					'.col {' .
						'font-size: 1.1em;' .
						'width: 33.33%;' .
					'}' .
					'.col--border {' .
						'border-right: 1px solid #E0DFDF;' .
						'min-height: 125px;' .
					'}' .
					'</style>' .
					'</head>' .
					'<body>' .
					'<div style="line-height:6px">&nbsp;<br>&nbsp;<br>&nbsp;</div>';
		$htmlend =  '<div>' .
						'<span class="section__title">' .
							' <span>1.</span> Your Team' .
						'</span><br><br>' .
						'<table cellspacing="0" align="left" cellpadding="10">' .
							'<tr>' .
								'<td class="col col--border" style="width:49%;">' .
									'<h3><b>Start</b></h3>' .
									$start .
								'</td>' .
								'<td class="col" style="width:49%;">' .
									'<h3><b>End</b></h3>' .
									$finish .
								'</td>' .
							'</tr>' .
						'</table>' .
					'</div>' .
					'<div style="background-color:#F9FAFA">' .
						'<span class="section__title">' .
							' <span>2.</span> Playing The Game' .
						'</span><br><br>' .
						'<table cellspacing="0" align="left" cellpadding="10">' .
							'<tr>' .
								'<td class="col col--border" style="width:49%;">' .
									'<h3><b>Activating Your Phone</b></h3>' .
									' On your mobile browser, visit <b>' . $config->fullUri . '</b>. Insert your email and activation code (Above you\'ll find one code for the leader and one code for all other team members)' .
								'</td>' .
								'<td class="col" style="width:49%;">' .
									'<h3><b>Challenges, Hint & Skip</b></h3>' .
									'You’ll receive different types of questions, from Q&A, riddles and trivia, to group photo ops and more! Need any help? Hit <span style="color:#ECA147">HINT</span> to get a clue. Still can’t figure it out? Hit <span style="color:#ECA147">SKIP</span>  to skip ahead.</td>' .
							'</tr>' .
						'</table>' .
					'</div>' .
					'<div>' .
						'<span class="section__title">' .
							' <span>3.</span> Scavenger Hunt Basics' .
						'</span><br><br>' .
						'<table cellspacing="13" align="left" cellpadding="0" style="font-size:16px">' .
							'<tr>' .
								'<td width="28">' .
									'<img src="' . $assetsDir . 's1.png" width="24" height="24">' .
								'</td>' .
								'<td width="600">' .
									'Receive challenges on your phone.' .
								'</td>' .
							'</tr>' .
							'<tr>' .
								'<td width="28">' .
									'<img src="' . $assetsDir . 's2.png" width="24" height="24">' .
								'</td>' .
								'<td width="600">' .
									'Complete each one to earn points.' .
								'</td>' .
							'</tr>' .
							'<tr>' .
								'<td width="28">' .
									'<img src="' . $assetsDir . 's3.png" width="24" height="24">' .
								'</td>' .
								'<td width="600">' .
									'Final scores will be adjusted and winners will be announced at the end!' .
								'</td>' .
							'</tr>' .
						'</table>' .
					'</div>' .
					'<div style="background-color:#F9FAFA">' .
						'<span class="section__title">' .
							' <span>4.</span> FAQ' .
						'</span><br><br>' .
						'<table cellspacing="0" align="left" cellpadding="10">' .
							'<tr>' .
								'<td class="col col--border">' .
									'<h3><b>Switching Phones</b></h3> ' .
									'Follow the activation instructions ' .
									'on the new phone. It’ll resume ' .
									'where you left off.' .
								'</td>' .
								'<td class="col col--border">' .
									'<h3><b>No Response?</b></h3> ' .
									'Refresh your browser. If the browser ' .
									'reloads the same screen after entering ' .
									'activation code, go into browser ' .
									'settings and accept cookies.' .
								'</td>' .
								'<td class="col">' .
									'<h3><b>Scoring</b></h3>' .
									'<ul>' .
										'<li>Answer <strong>correctly</strong> &ndash; get full points!</li>' .
										'<li>Use a <strong>hint</strong>, then answer correctly - get half points.</li>' .
										'<li>Once you’re done with a challenge, you can’t go back!</li>' .
									'</ul>' .
								'</td>' .
							'</tr>' .
						'</table>' .
					'</div>' .
					'<div style="border-bottom:1px solid #f1f1f1">' .
						'<table cellspacing="0" align="left" cellpadding="10" class="footer">' .
							'<tr valign="bottom">' .
								'<td class="col" valign="bottom" style="width:66.66%">' .
									'<h3><b>Posting on Instagram, Twitter, Facebook, etc.?</b></h3>' .
									'Make sure to use the hashtag #strayboots so we can see too!' .
								'</td>' .
								'<td class="col" style="color:#ECA147;font-size:1.3em;font-weight:bold">' .
									'<br><br>' .
									'Need Help? 877.787.2929' .
								'</td>' .
							'</tr>' .
						'</table>' .
					'</div>' .
					'</body>' .
					'</html>';
		foreach ($oh->getTeams() as $t => $team) {
			$this->AddPage();
			$this->writeHTML(
				$htmlstart .
				'<div style="border-bottom:1px solid #f1f1f1">' .
					'<table cellspacing="0" align="right" cellpadding="0" style="font-size:16px">' .
						'<tr valign="bottom">' .
							'<td width="440"></td>' .
							'<td align="left" style="background-color:#eca147;line-height:33px;font-weight:bold;color:#FFF" valign="middle">' .
								'&nbsp;&nbsp;' . (is_null($team->name) ? 'Team <span style="color:#000">' . ($t + 1) . '</span>' : $team->name) .
							'</td>' .
							'<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>' .
						'</tr>' .
						'<tr valign="bottom">' .
							'<td width="440" style="text-align:left"><br><br>' . $order. '<br>' . $city . ' - ' . $hunt . '</td>' .
							'<td align="left" style="color:#000" valign="middle">' .
								'<br>' .
								'<br>' .
								'<span style="font-weight:bold">Activation Code:</span><span style="line-height:1.4"><br></span>' . "\n" .
								'<span style="font-weight:bold;color:#ECA147">Leader:</span> ' . $team->activation_leader . '<span style="line-height:1.4"><br></span>' . "\n" .
								'<span style="font-size:12px;line-height:1.5">Player (optional): ' . $team->activation_player . '</span>' .
							'</td>' .
							'<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>' .
						'</tr>' .
					'</table>' .
					'<br>' .
				'</div>' .
				$htmlend,
				true, false, true, false, '');
		}

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
