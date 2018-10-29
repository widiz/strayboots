<?php

define('APP_PATH', realpath('..'));
define('PUBLIC_PATH', __DIR__ . '/');
$config = require '../config/config.php';

$img = '/img/11600.jpg';
$imgWidth = 1598;
$imgHeight = 1039;
if (preg_match('/^(\d+\/(\d+|chat)\/[0-9a-z_]+)(\.wm)?\.(jpg|png|gif)$/', $f = filter_input(INPUT_GET, 'f', FILTER_SANITIZE_STRING), $matches)) {
	$basePath = $config->application->frontUploadsDir->path;
	$path = $basePath . $matches[1] . $matches[3] . '.' . $matches[4];
	if (realpath($path) && ($imgSizes = getimagesize($path))) {
		$img = substr($basePath, strlen(dirname($basePath, 2))) . $matches[1] . '.wm.' . $matches[4];
		$imgWidth = $imgSizes[0];
		$imgHeight = $imgSizes[1];
	}
}
if ($img === false) {
	header('Location: ' . $config->fullUri);
	exit;
}
$img = $config->fullUri . $img;
$title = filter_input(INPUT_GET, 'title', FILTER_SANITIZE_STRING);
$link = filter_input(INPUT_GET, 'link', FILTER_VALIDATE_URL);
$description = filter_input(INPUT_GET, 'description', FILTER_SANITIZE_STRING);

$escaper = new Phalcon\Escaper();

ob_start(function($c){
	return str_replace(["\t", "\n"], '', $c);
});
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Strayboots</title>
	<meta property="og:title" content="<?= $escaper->escapeHtmlAttr(empty($title) ? 'We just successfully finished our Strayboots scavenger hunt!' : $title) ?>" />
	<meta property="og:description" content="<?= $escaper->escapeHtmlAttr(empty($description) ? 'That was so much fun!!! #teambuilding #scavengerhunt @strayboots' : $description) ?>" />
	<meta property="og:image" content="<?= $img ?>" />
	<meta property="og:image:width" content="<?= $imgWidth ?>"/>
	<meta property="og:image:height" content="<?= $imgHeight ?>"/>
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,600,300,800|Roboto:400,500,700,900,300' rel='stylesheet' type='text/css'>
	<style type="text/css">
		* {
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}
		html, body {
			background: #e6e6e6;
			font-family: 'Open Sans', 'Roboto', sans-serif;
		}
		html {
			height: 100%;
		}
		body {
			background: url(/img/11600.jpg) 50% 50% no-repeat;
			background-size: 100%;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			background-size: cover;
			background-attachment: fixed;
			min-height: 99.99%;
			color: #FFF;
		}
		.wrapper {
			padding: 50px 25px;
			text-align: center;
			max-width: 720px;
			margin: 0 auto;
		}
		h1 {
			font-weight: 300;
			font-size: 30px;
			line-height: 1.35;
			margin: 20px 0;
		}
		p {
			font-weight: 400;
			font-size: 20px;
			line-height: 1.4;
			margin-bottom: 15px;
		}
		img {
			max-width: 100%;
			border: 0;
		}
		.img {
			display: block;
			margin: 30px auto;
			border: 4px solid transparent;
			border-radius: 15px;
		}
		a.btn {
			text-decoration: none;
			border: 2px solid #f39c12;
			padding: 15px 80px;
			font-size: 17px;
			color: #f39c12;
			font-weight: 700;
			margin-top: 15px;
			display: inline-block;
			-webkit-border-radius: 0;
			-moz-border-radius: 0;
			border-radius: 0;
			background: transparent;
			-webkit-transition: background 0.2s linear, box-shadow 0.2s linear;
			-moz-transition: background 0.2s linear, box-shadow 0.2s linear;
			-ms-transition: background 0.2s linear, box-shadow 0.2s linear;
			-o-transition: background 0.2s linear, box-shadow 0.2s linear;
			transition: background 0.2s linear, box-shadow 0.2s linear;
		}
		a.btn:focus {
			background: #FFF;
			outline: none;
			-webkit-box-shadow: 6px 13px 44px -1px rgba(156,151,156,1);
			-moz-box-shadow: 6px 13px 44px -1px rgba(156,151,156,1);
			box-shadow: 6px 13px 44px -1px rgba(156,151,156,1);
		}
		@media all and (max-width: 480px) {
			.wrapper {
				padding-top: 40px;
			}
			h1 {
				font-size: 24px;
			}
			p {
				font-size: 16px;
			}
			a.btn {
				padding: 15px 60px;
			}
		}
		@media all and (max-width: 360px) {
			h1 {
				font-size: 20px;
			}
			p {
				font-size: 14px;
			}
		}
	</style>
</head>
<body>
	<div class="wrapper">
		<div class="logo">
			<a href="https://www.strayboots.com/?utm_source=go&utm_campaign=photo&utm_content=learnmore"><img src="https://www.strayboots.com/media/static/img/home/logo.png" height="66" width="215"></a>
		</div>
		<h1>Check out the cool photo from our Strayboots scavenger hunt!</h1>
		<img src="<?= $img ?>" height="auto" width="<?= $imgWidth ?>" alt="" class="img" onload="this.style['border-color']='#f39c12'">
		<p>This is so much FUN, you gotta check it out for your team</p>
		<a class="btn" href="<?= $escaper->escapeHtmlAttr(preg_replace('/\/\/www.strayboots.com\/?$/', '//www.strayboots.com/?utm_source=go&utm_campaign=photo&utm_content=learnmore', empty($link) ? 'https://www.strayboots.com' : $link)) ?>">Learn More</a>
	</div>
</body>
</html>
<? ob_end_flush() ?>