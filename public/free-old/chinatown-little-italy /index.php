<?php
$orderHuntID = 322;
require '../../../apps/whitelabel/header.php';
?>
	<style type="text/css">
		html {
			height: 100%;
		}
		body {
			background: url(/img/bg-1.jpg) 50% 50px no-repeat;
			background-size: 100%;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			background-size: cover;
			background-attachment: fixed;
			min-height: 99.99%;
			padding-top: 55px;
		}
		label.error {
			color: #fff;
		}
		h1 {
			color: #fff;
			padding-bottom: 20px;
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
		<h1>Welcome to your FREE Chinatown & Little Italy<br>Strayboots scavenger hunt!</h1>
<? require '../../../apps/whitelabel/form.php'; ?>
	</div>
<? require '../../../apps/whitelabel/footer.php'; ?>