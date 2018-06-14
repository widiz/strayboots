<?php

if (isset($_GET['app'])) {
	$session = new Phalcon\Session\Adapter\Files();
	$session->start();

	if ($session->get('playerID') > 0) {
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		header('Location: /');
		exit;
	}
}

require '../../apps/whitelabel/header.php';
?>
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
		.containerS{
			display: table;
			width: 100%;
			padding: 0 20px;
		}
		.rowS{
			display: table-row;
			background: black;
		}
		.col-3{
			display: table-cell;
			width: 25%;
			vertical-align: bottom; 
		}
		.col-9{
			display: table-cell;
			width: 75%;
			padding-left: 20px; 
		}

		.rowS .col-9 h2{
			color:white;
			font-size: 22px;
			text-align:left;
			margin-top: 20px;
			margin-bottom: 5px;
		}

		.rowS .col-9 p{
			color:white;
			font-size: 16px;
			text-align:left;
			margin-top: 0; 
			margin-bottom: 10px;
		}

		.rowS .col-3.imgIcon{
			background: url('/free/no-image.jpg');
			background-repeat: no-repeat;
			background-size: cover;
			background-position: center center;
		}

		hr{
			    border: #717171 1px solid;
			    margin: 15px 0;
		}

		.buttonS{
			width: 150px;
			background: transparent;
			border: 2px solid #F39C12;
			color:white;
			padding: 8px;
			margin-right: 15px;
			margin-bottom: 15px;
		}

		.rowS .col-9 p i{
			color:#F39C12;
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
		<h1>Welcome to your FREE Strayboots <br> scavenger hunt!</h1>
		<a href="/free/wall-street">
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>Wall Street</h2>
						<p><i>Starting Point:</i> Corner of Wall Street & Broad Street. <br> <i>Hunt Duration:</i> 1 1/2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

		<hr>

		<a href="/free/union-square-and-flatiron">
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>Union Square & Flatiron </h2>
						<p><i>Starting Point:</i> Union Square (corner of 17th and Broadway). <br> <i>Hunt Duration:</i> 1 1/2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

		<hr>

		<a href="/free/bryant-park-and-midtown">	
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>Bryant Park & Midtown </h2>
						<p><i>Starting Point:</i> Fountain in Bryant Park. <br> <i>Hunt Duration:</i> 2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

		<hr>

		<a href="/free/the-village">
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>The Village</h2>
						<p><i>Starting Point:</i> Arch in Washington Square Park. <br> <i>Hunt Duration:</i> 1 1/2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

		<hr>

		<a href="/free/chinatown-little-italy">
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>Chinatown & Little Italy </h2>
						<p><i>Starting Point:</i> Corner of Canal Street and Centre Street. <br> <i>Hunt Duration:</i> 1 1/2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

		<hr>

		<a href="/free/grand-central-terminal">
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>Grand Central Terminal</h2>
						<p><i>Starting Point:</i> Grand Central Main Concourse - clock. <br> <i>Hunt Duration:</i> 1 1/2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

		<hr>

		<a href="/free/american-museum">
			<div class="containerS">
				<div class="rowS">
					<div class="col-3 imgIcon">
						
					</div>
					<div class="col-9">
						<h2>American Museum of Natural History</h2>
						<p><i>Starting Point:</i> Main Lobby. <br> <i>Hunt Duration:</i> 2 hours</p>
						<div class="buttonS">Start Now!</div>
					</div>
				</div>
			</div>
		</a>

	</div>
<? require '../../apps/whitelabel/footer.php'; ?>