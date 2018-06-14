<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Wix</title>
	<link rel="stylesheet" type="text/css" href="/template/css/bootstrap.min.css">
	<style type="text/css">
		header, section, footer {
			padding: 15px;
		}
		#loader {
			display: block;
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: #FFF;
			background: rgba(255, 255, 255, .65);
		}
		.loader {
			position: absolute;
			top: 50%;
			left: 50%;
			margin: -60px 0 0 -60px;
			border: 16px solid #f3f3f3;
			border-top: 16px solid #3498db;
			border-radius: 50%;
			width: 120px;
			height: 120px;
			animation: spin 2s linear infinite;
		}
		@-webkit-keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		@-moz-keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
	</style>
</head>
<body>
	<header>
		<div class="container">
			<div class="col-md-12">Wix Login</div>
		</div>
	</header>
	<section>
		<div class="container">
			<div class="col-md-12">
				<form method="post" id="loginform" class="form-signin">
					<input type="hidden" name="logout" value="1" />
					<input type="email" class="form-control" name="email" placeholder="Email" required autofocus /><br>
					<input type="text" class="form-control" name="activation" placeholder="Activation Code" required /><br>
					<input class="btn btn-lg btn-primary" value="Login" type="Submit" />
				</form>
			</div>
		</div>
	</section>
	<footer>
		<div class="container">
			<div class="col-md-12">
				<a href="https://www.strayboots.com/">Strayboots.com</a>
			</div>
		</div>
	</footer>
	<div id="loader">
		<div class="loader"></div>
	</div>
	<script src="/template/js/jquery-2.1.1.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(function(){
			var $loader = $('#loader').fadeOut(200);
			$('#loginform').submit(function(){
				$loader.stop().fadeIn(250);
				$.ajax({
					url: '/?ajaxlogin=1',
					type: 'POST',
					dataType: 'json',
					data: $(this).serializeArray(),
					success: function(data){
						if (typeof data === 'object' && data.success === true && data.redirect)
							document.location.href = data.redirect;
						else
							this.error(data);
					},
					error: function(data){
						if (typeof data === 'object' && typeof data.messages === 'object') {
							var msg = [];
							for (var i in data.messages) {
								for (var j in data.messages[i]) {
									msg.push(/*i + ': ' + */data.messages[i][j]);
								}
							}
							alert(msg.length ? msg.join("\n") : 'Unknown error; please try again');
						}
						$loader.stop().fadeOut(250);
					}
				});
				return false;
			});
		});
	</script>
</body>
</html>