<?php

define('APP_PATH', dirname(dirname(__DIR__)));

function after_body() {
	echo <<<EOF
<script>
window.fbAsyncInit=function(){
	FB.init({
		appId: '302564933413297',
		status: true,
		xfbml: true,
		version: 'v2.7'
	});
};
</script>
<script src="//connect.facebook.net/en_US/sdk.js" id="facebook-jssdk"></script>
<script type="text/javascript" src="/template/js/jquery-2.1.1.js"></script>
<script>
$('#shareblock a.facebook').click(function(){
	FB.ui({
		method: 'share',
		href: document.location.protocol + '//' + document.location.host + '/share.php?' + $.param({
			title: 'I just successfully finished my Strayboots scavenger hunt!',
			description: 'That was so much fun!!! #teambuilding #scavengerhunt @strayboots'
		}),
	});
});
</script>
EOF;
}

require APP_PATH . '/apps/whitelabel/header.php';
?>
	<title>Strayboots tours</title>
	<link rel="stylesheet" href="/template/css/plugins/select2/select2.min.css" />
	<style type="text/css">
		html {
			height: 100%;
		}
		body {
			background: url(/img/bgn-2.jpg) 50% 50px no-repeat;
			background-size: 100%;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			background-size: cover;
			background-attachment: fixed;
			min-height: 99.99%;
			padding-top: 55px;
		}
		h1 {
			color: #fff;
			padding-bottom: 15px;
		}
		.navbar-default {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			background: #F39C12;
			box-shadow: none;
			border-bottom: 2px solid #000;
		}
		p {
			color: #fff;
		}
		#shareblock {
			margin-top: 40px;
		}
		.btn-primary {
			color: #fff;
			background-color: #337ab7;
			border-color: #2e6da4;
			width: auto;
		}
	</style>
</head>
<body>
	<div class="navbar-default navbar-fixed-top">
		<div class="container">
			<a class="navbar-brand logo" href="/">
				<img src="/img/logo.png" alt="Strayboots" height="50" width="164">
			</a>
		</div>
	</div>
	<div class="content-wrapper">
		<h1>Thank you for using Strayboots!</h1>
		<div class="row">
			<div class="grid-100">
				<p>
					Your photos will be sent to the email you registered with. If you have any questions, please email us at <a href="mailto:support@strayboots.com">support@strayboots.com</a><br>
					We hope you had fun today, and be sure to spread the word about Strayboots.
				</p>
				<div id="shareblock">
					<a href="javascript:;" class="btn btn-primary share facebook">Share with Facebook</a>
				</div>
			</div>
		</div>
	</div>
<? require APP_PATH . '/apps/whitelabel/footer.php'; ?>