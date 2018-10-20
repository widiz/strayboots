		<form method="post" id="activation-form" action="/">
<? if (defined('password')): ?>
			<div class="row">
				<div class="grid-100">
					<input type="name" id="nameField" name="name" placeholder="Name" />
					<label class="error" id="nameError"></label>
				</div>
			</div>
<? else: ?>
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
<? endif ?>
			<div class="row">
				<div class="grid-100">
					<input type="email" id="emailField" name="email" placeholder="Email" />
					<label class="error" id="emailError"></label>
				</div>
			</div>
<? if (defined('password')): ?>
			<div class="row">
				<div class="grid-100">
					<input type="password" id="passwordField" name="password" placeholder="Password" />
					<label class="error" id="passwordError"></label>
				</div>
			</div>
<? endif ?>
			<div class="options row">
				<div class="grid-40"><input type="submit" class="btn btn-success" value="Start now" /></div>
				<div class="grid-60"><button class="btn btn-fb" id="fblogin">Facebook Login</button></div>
			</div>
			<div style="margin-top:20px;font-size:12px">
By signing up, you agree to Strayboots <a href="http://www.strayboots.com/terms-of-service" target="_blank">Terms of Use</a> and <a href="http://www.strayboots.com/privacy-policy" target="_blank">Privacy Policy</a>
			</div>
			<input type="hidden" name="network_id" id="networkIdField" />
			<input type="hidden" name="network" id="networkField" />
			<input type="hidden" name="logout" value="1" />
			<input type="hidden" name="id" value="<?= $orderHuntID ?>" />
		</form>
