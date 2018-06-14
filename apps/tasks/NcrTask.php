<?php

use Play\Frontend\Controllers\NcrController;

class NcrTask extends TaskBase
{
	public function mainAction($args = [])
	{
		echo 'Ncr emails v' . VERSION . PHP_EOL;
		echo 'Please choose an action (pre/post)' . PHP_EOL;
	}

	public function preAction($args = [])
	{
		echo 'Ncr pre-event email v' . VERSION . PHP_EOL;
		$cache = isset($args[0]) ? (bool)$args[0] : true;
		echo ($cache ? 'Using cache' : 'Not using cache') . PHP_EOL. PHP_EOL;

		$orderHunts = OrderHunts::findByOrderId(NcrController::ORDER_ID);
		echo 'Found ' . count($orderHunts) . ' order hunts' . PHP_EOL;

		$ouri = $this->config->fullUri;
		$this->config->fullUri .= '/ncr';

		$html = <<<EOF
<center>
<table align="center" border="0" dir="ltr" width="100%" style="max-width:650px;border:0;float:none">
	<tr>
		<td style="text-align:left">
			<div style="font-size:10px;line-height:1"> <br></div>
			<img src="{$ouri}/img/ncr/bgncr.png" width="100%" border="0" height="auto" style="border:0;max-width:100%">
			<p><span style="font-family:'Open Sans',sans-serif">&nbsp;</span></p>
			<p><span style="font-family:'Open Sans',sans-serif; color: black;">Hi %name%!<br> <br> We hope you&rsquo;re getting excited for the&nbsp;<strong>All Routes Lead to Midtown Scavenger Hunt</strong>! We&rsquo;re definitely getting excited for you!</span><br></p>
			<p><span style="font-family:'Open Sans',sans-serif; color: black;">As promised, we&rsquo;ve attached your instruction sheet with your hunt activation code. Armed with a mobile phone, the link below and this activation code, you have everything you need to get started.</span></p>
			<p><span style="font-family:'Open Sans',sans-serif; color: black;"><br> <strong>GETTING STARTED:</strong></span></p>
			<ul>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">First and foremost,&nbsp;you will not be playing through an app. You&rsquo;ll be playing through your phone&rsquo;s web browser.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">At the start time on the day of your hunt, you will simply enter your NCR email address and activation code at&nbsp;</span><span style="color: windowtext;"><a href="{$this->config->fullUri}"><span style="font-family:'Open Sans',sans-serif; color: blue;">{$this->config->fullUri}</span></a></span><span style="font-family:'Open Sans',sans-serif;">.</span></li>
			</ul>
			<p><br><strong><span style="font-family:'Open Sans',sans-serif; color: black;">GOOD TO KNOW:</span></strong></p>
			<ul style="margin-top: 0cm;">
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">Please make sure you bring cash and/or credit card as you will need to purchase one MARTA ride during the hunt. Remember the $20 reimbursement you receive once you complete the hunt is meant to cover this purchase plus your other commute costs for the day.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">The hunt challenges will ask you to solve riddles, find cool locations, and in some cases, upload creative photos with your group. </span><span style="font-family:'Open Sans',sans-serif;">Be on the lookout for bonus questions that will help you earn prizes too!</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">You&rsquo;ll earn assigned points for your correct answers and uploads.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">If you get stuck at any time, you can hit the HINT button for a quick hint on how to solve a riddle or the SKIP button to skip to the next question. Keep in mind, you can&rsquo;t go back! If you ask for a hint, you&rsquo;ll lose half of the available points, and if you skip, you lose all of them.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">Everyone will start on the NORTH side of the Midtown MARTA Station. This location is north of 10th Street at The Lift statue on Peachtree Walk NE. At the designated start time, the hunt will go live, and teams will go their separate ways. No two teams will take the same route.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">When time is called, the team with the most points wins!</span></li>
			</ul>
			<p><br><strong><span style="font-family:'Open Sans',sans-serif; color: black;">ENDING THE HUNT:</span></strong></p>
			<ul>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">Once time is called, your team will stop hunting, even if you haven't finished all of the challenges.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">You will make your way to Woodruff Arts Center using MARTA. The stop for Woodruff Arts Center is the Arts Center Station, north of the Midtown Station.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">Once you arrive at Woodruff Arts Center, look for the &ldquo;Merry Goes Zoo&rdquo; installation at the piazza by the High Museum. We will be set up there ready to high-five you for a job well done and provide your team leaders with the reimbursements for your team.</span></li>
			<li style="color: black; tab-stops: list 36.0pt;"><span style="font-family:'Open Sans',sans-serif;">If you run into any issues along the hunt, give us a call at&nbsp;</span><span style="color: windowtext;"><a href="tel:%28877%29%20787-2929"><span style="font-family:'Open Sans',sans-serif; color: blue;">(877) 787-2929</span></a></span><span style="font-family:'Open Sans',sans-serif;">.&nbsp;</span></li>
			</ul>
			<p><span style="font-family:'Open Sans',sans-serif; color: black;"><br>Have fun! You'll quickly realize that all routes truly do lead to Midtown!</span></p>
		</td>
	</tr>
</table>
</center>
EOF;

		$attachmentParams = [
			//'inline' => [APP_PATH . '/apps/common/assets/bgncr.png']
		];

		$sent = 0;
		foreach ($orderHunts as $orderHunt) {
			echo 'Processing order hunt #' . $orderHunt->id . PHP_EOL;
			$teams = $orderHunt->getTeams(true);
			echo "\t" . 'Found ' . count($teams) . ' teams' . PHP_EOL;
			foreach ($teams as $tid => $team) {
				echo "\t\t" . 'Processing team #' . $team->id . PHP_EOL;
				$players = $team->getPlayers();
				echo "\t\t" . 'Found ' . count($players) . ' players' . PHP_EOL;
				$leader = null;
				$members = [];
				foreach ($players as $player) {
					if ($player->id == $team->leader)
						$leader = [$player->id, $player->email, implode(' ', array_filter([$player->first_name, $player->last_name]))];
					else
						$members[] = [$player->id, $player->email, implode(' ', array_filter([$player->first_name, $player->last_name]))];
				}
				if ($leader) {
					$cacheKey = $cache ? SB_PREFIX . 'ncr:pe:' . $leader[0] : false;
					if ($cacheKey && $this->redis->exists($cacheKey)) {
						echo "\t\t\t" . 'Team leader (' . $leader[0] . ') in cache... skipping' . PHP_EOL;
					} else {
						echo "\t\t\t" . 'Processing team leader (' . $leader[0] . ').';
						$pdf = new OrderHuntPDFNCR($orderHunt, $this->timeFormat, true, false, $team, $tid + 1);
						echo '.';
						$attachments = [
							[
								'fileContent' => $pdf->getOutput(),
								'filename' => $pdf->getBaseName()
							]
						];
						if ($this->sendMail(/*"ido@strayboots.com"*/$leader[1], 'Your NCR Scavenger Hunt Instructions', null, str_replace('%name%', $leader[2], $html), $attachments, [/*'bcc'=>"shay12tg@gmail.com"*/], $attachmentParams)) {
						//if (usleep(100000)|| true) {
							echo '. done' . PHP_EOL;
							$sent++;
							if ($cacheKey)
								$this->redis->set($cacheKey, 1, 86400*3);
						} else {
							echo '. failed to send!!!!!!!!!' . PHP_EOL;
						}
						//die;

					}
				} else {
					echo "\t\t\t" . 'Failed to find team leader (' . (int)$team->leader . ')' . PHP_EOL;
				}
				$numMembers = count($members);
				if ($numMembers) {
					echo "\t\t\t" . 'Processing members (' . $numMembers . ')' . PHP_EOL;

					foreach ($members as $member) {
						$cacheKey = $cache ? SB_PREFIX . 'ncr:pre:' . $member[0] : false;
						if ($cacheKey && $this->redis->exists($cacheKey)) {
							echo "\t\t\t\t" . 'Team member (' . $member[0] . ') in cache... skipping' . PHP_EOL;
						} else {
							echo "\t\t\t\t" . 'Processing team member (' . $member[0] . ').';
							$pdf = new OrderHuntPDFNCR($orderHunt, $this->timeFormat, false, true, $team, $tid + 1);
							echo '.';
							$attachments = [
								[
									'fileContent' => $pdf->getOutput(),
									'filename' => $pdf->getBaseName()
								]
							];
							if ($this->sendMail(/*"ido@strayboots.com"*/$member[1], 'Your NCR Scavenger Hunt Instructions', null, str_replace('%name%', $member[2], $html), $attachments, [/*'bcc'=>"shay12tg@gmail.com"*/], $attachmentParams)) {
							//if (usleep(100000)|| true) {
								echo '. done' . PHP_EOL;
								$sent++;
								if ($cacheKey)
									$this->redis->set($cacheKey, 1, 86400*3);
							} else {
								echo '. failed to send!!!!!!!!!' . PHP_EOL;
							}
							//die;
						}
					} 
				} else {
					echo "\t\t\t" . 'No members found' . PHP_EOL;
				}
			}
		}


		echo PHP_EOL . 'Sent ' . number_format($sent) . ' emails' . PHP_EOL;
	}

	public function postAction($args = [])
	{
		echo 'Ncr post-event email v' . VERSION . PHP_EOL;
		$cache = isset($args[0]) ? (bool)$args[0] : true;
		echo ($cache ? 'Using cache' : 'Not using cache') . PHP_EOL. PHP_EOL;

		//$orderHunts = OrderHunts::findByOrderId(351);
		$orderHunts = OrderHunts::findByOrderId(NcrController::ORDER_ID);
		echo 'Found ' . count($orderHunts) . ' order hunts' . PHP_EOL;

		$html = <<<EOF
<center>
<table align="center" border="0" dir="ltr" width="100%" style="max-width:650px;border:0;float:none">
	<tr>
		<td style="text-align:left">
			<div style="font-size:10px;line-height:1"> <br></div>
			<img src="{$this->config->fullUri}/img/ncr/bgncr.png" width="100%" border="0" height="auto" style="border:0;max-width:100%">
			<p><span style="color:#58585a;font-family:'Open Sans',sans-serif">&nbsp;</span></p>
			<p><span style="color:#58585a;font-family:'Open Sans',sans-serif">%name%</span><br></p>
			<p><span style="color:#58585a;font-family:'Open Sans',sans-serif">Congratulations to you and your team on finishing the </span><strong><span style="color:#0099bf;font-family:'Open Sans',sans-serif">All Routes Lead to Midtown Scavenger Hunt</span></strong> <span style="color:#58585a;font-family:'Open Sans',sans-serif">adventure! We hope you had a blast getting to know your commute options and the fun points of interest, merchants and restaurants around Midtown Atlanta.</span></p>
			%mvp%
			<p><span style="color:#58585a;font-family:'Open Sans',sans-serif"><br>Congrats to the top ranked team for today&rsquo;s hunt! Each team member will be receiving \$100 MARTA or GRTA passes. iNCRedible job! Other winning teams will be announced on Friday, October 27th once all hunts are concluded, and we have a chance to review everyone&rsquo;s photos and trivia answers in detail. For now, see how you did against other teams and recap the fun <a href="%eurl%">here</a>.</span></p>
			<p><span style="color:#58585a;font-family:'Open Sans',sans-serif">Until then, be sure to share your fun adventures on social media and tag us @NCRCorporation. Don’t forget to use #NCRlife on all your posts!</span></p>
		</td>
	</tr>
</table>
</center>
EOF;

		$attachmentParams = [
			//'inline' => [APP_PATH . '/apps/common/assets/bgncr.png']
		];

		$sent = 0;
		foreach ($orderHunts as $orderHunt) {
			if ($orderHunt->id != 1238)
				continue;
			echo 'Processing order hunt #' . $orderHunt->id . PHP_EOL;
			if (!$eurl = $this->redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
				$endlink = $this->config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($this->crypt->encryptBase64($orderHunt->id));
				if ($eurl = $this->bitly($endlink, $this->config->bitly))
					$this->redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
				else
					$eurl = $endlink;
			}

			$teamStatus = array_slice($orderHunt->getTeamsStatus(), 0, 3);

			$files = $orderHunt->getFiles();

			$itxt = ['1<sup>st</sup>'/*, '2<sup>nd</sup>', '3<sup>rd</sup>'*/];
			$mvp = false;
			for ($ix = count($teamStatus); $ix < 3; $ix++)
				$itxt[$ix] = '';
			$images = [''/*, '', ''*/];
			$ix = 0;
			foreach ($teamStatus as $t => $ts) {
				foreach ($files as $f) {
					if ($f[1] == $ts['id']) {
						$images[$ix++] = '<a href="' . $eurl . '#photos"><img src="' . $this->config->fullUri . $this->config->application->frontUploadsDir->uri . $orderHunt->id . '/' . preg_replace('/\.(jpg|png|gif)$/', '.thumbnail.$1', $f[0]) . '" style="max-width:100%"></a>';
						$mvp = true;
						//continue 2;
						break 2;
					}
				}
				$itxt[$ix++] = '';
				break;
			}

			if ($mvp) {
				$mvp = <<<EOF
	<br>
	<p><strong><span style="font-family:'Open Sans',sans-serif; color: #58585a">TOP RANKED TEAM:</span></strong></p>
	<div>
	<table cellspacing="0" cellpadding="0" border="0" width="100%" align="left" style="max-width:100%;margin:0;float:none">
		<tr>
			<td>
				<table cellspacing="0" cellpadding="0" border="0" width="160" align="left" style="max-width:160px;margin:0 auto 0 0;float:none">
					<tr>
						<td width="100%%" style="text-align:left;vertical-align:middle">{$images[0]}</td>
					</tr>
					<tr>
						<td width="100%%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;font-family:'Open Sans',sans-serif; color: #58585a">{$itxt[0]}</div></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</div>
EOF;
	/*<table cellspacing="0" cellpadding="0" border="0" width="580" style="max-width:100%;margin:0 auto;float:none">
		<tr>
			<td width="33.3%" style="text-align:center;vertical-align:middle">{$images[0]}</td>
			<td width="33.3%" style="text-align:center;vertical-align:middle">{$images[1]}</td>
			<td width="33.3%" style="text-align:center;vertical-align:middle">{$images[2]}</td>
		</tr>
		<tr>
			<td width="33.3%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;font-family:'Open Sans',sans-serif; color: #58585a;max-width:160px;margin:0 auto">{$itxt[0]}</div></td>
			<td width="33.3%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;font-family:'Open Sans',sans-serif; color: #58585a;max-width:160px;margin:0 auto">{$itxt[1]}</div></td>
			<td width="33.3%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;font-family:'Open Sans',sans-serif; color: #58585a;max-width:160px;margin:0 auto">{$itxt[2]}</div></td>
		</tr>
	</table><br>*/
			} else {
				$mvp = '';
			}
			$mvpHTML = str_replace(['%mvp%', '%eurl%'], [$mvp, $eurl], $html);

			$teams = $orderHunt->getTeams(true);
			echo "\t" . 'Found ' . count($teams) . ' teams' . PHP_EOL;
			foreach ($teams as $team) {
				echo "\t\t" . 'Processing team #' . $team->id . PHP_EOL;
				$players = $team->getPlayers();
				$numPlayers = count($players);
				if ($numPlayers === 0)
					echo "\t\t" . 'No players found' . PHP_EOL;
				else
					echo "\t\t" . 'Found ' . $numPlayers . ' players' . PHP_EOL;
				foreach ($players as $player) {
					$member = [$player->id, $player->email, implode(' ', array_filter([$player->first_name, $player->last_name]))];
					$cacheKey = $cache ? SB_PREFIX . 'ncr:poe:' . $member[0] : false;
					if ($cacheKey && $this->redis->exists($cacheKey)) {
						echo "\t\t\t" . 'Team member (' . $member[0] . ') in cache... skipping' . PHP_EOL;
					} else {
						echo "\t\t\t" . 'Processing team member (' . $member[0] . ').';
						echo '.';
						if ($this->sendMail(/*"ido@strayboots.com"*/$member[1], 'Your NCR Scavenger Hunt Results', null, str_replace('%name%', $member[2] ? 'Dear ' . $member[2] . ':' : '', $mvpHTML), [], [/*'bcc'=>["shay12tg@gmail.com","ariel@safronov.co.il"]*/], $attachmentParams)) {
						//if (usleep(100000)|| true) {
							echo '. done' . PHP_EOL;
							$sent++;
							if ($cacheKey)
								$this->redis->set($cacheKey, 1, 86400*3);
						} else {
							echo '. failed to send!!!!!!!!!' . PHP_EOL;
						}
						//die;
					}
				}
			}
		}

		echo PHP_EOL . 'Sent ' . number_format($sent) . ' emails' . PHP_EOL;
	}
}
