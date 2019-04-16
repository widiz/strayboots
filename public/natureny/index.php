<?php
$orderHuntID = 885;
require '../../apps/whitelabel/header.php';
?>
<style type="text/css">
	body, input, h1, .btn{
		font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
		font-weight: 700;
	}
</style>
</head>
<body>
	<nav class="navbar navbar-default navbar-fixed-top">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand logo" href="https://www.strayboots.com/">
					<img src="/img/logo.png" alt="Strayboots">
				</a>
			</div>
		</div>
	</nav>
	<div class="content-wrapper">
		<img src="TNCLogoPrimary_RGB.png" />
		<h1>Welcome to the Governors Island <br> Scavenger Hunt!</h1>
	
		<form method="post" id="activation-form" action="/">
			<div class="row">
				<div class="grid-50">
					<input type="text" id="firstNameField" name="first_name" placeholder="First Name" />
					<label class="error" id="firstNameError"></label>
				</div>
				<div class="grid-50">
					<input type="text" id="lastNameField" name="last_name" placeholder="Last Name" />
					<label class="error" id="lastNameError"></label>
				</div>
			</div>
			<div class="row">
				<div class="grid-100">
					<input type="email" id="emailField" name="email" placeholder="Email" />
					<label class="error" id="emailError"></label>
				</div>
			</div>
			<div class="options row">
				<div class="grid-40"><input type="submit" class="btn btn-success" value="Start now" /></div>
				<div class="grid-60"><button class="btn btn-fb" id="fblogin">Facebook Login</button></div>
			</div>
			<div style="margin-top:20px;font-size:12px">
			* By joining the scavenger hunt, you agree to The Nature Conservancy’s Waiver of <a href="https://www.nature.org/ourinitiatives/regions/northamerica/unitedstates/newyork/getinvolved/waiver-of-liability-ny.xml" target="_blank" style="color: blue;">Liability</a>, <a href="https://www.nature.org/about-us/governance/terms-of-use/index.htm" target="_blank" style="color: blue;">Terms of Use</a>, <a href="https://www.nature.org/about-us/governance/privacy-policy.xml" target="_blank" style="color: blue;">Privacy Policy</a> and to receive the latest in conservation news, special event opportunities, and updates. The Nature Conservancy respects your privacy and will not sell, rent or exchange your e-mail address.
			</div>
			<input type="hidden" name="network_id" id="networkIdField" />
			<input type="hidden" name="network" id="networkField" />
			<input type="hidden" name="logout" value="1" />
			<input type="hidden" name="id" value="<?= $orderHuntID ?>" />
		</form>
	</div>
<? require '../../apps/whitelabel/footer.php'; ?>