$(function(){
	$('#activation-form').validate();
	function showEmailLogin() {
		$('#activation-fb').fadeOut(250);
		$('#activation-email').css('visibility', 'visible').hide().fadeIn(300);
	}
	$('.content-wrapper .subxbtn.email').click(function(){
		showEmailLogin();
		return false;
	});
	$('.content-wrapper .subxbtn.facebook').click(function(){
		window.fbFunc(function(){
			try {
				FB.login(function(response) {
					if (response.authResponse) {
						FB.api('/me?fields=id,first_name,last_name,email', function(response) {
							if (typeof response != 'object' || !(response.id > 0))
								throw '';
							$('#firstNameField').val(response.first_name);
							$('#lastNameField').val(response.last_name);
							$('#emailField').val(response.email).prop('readonly', true);
							$('#networkIdField').val(response.id);
							$('#networkField').val(1);
							showEmailLogin();
						});
					} else {
						throw '';
					}
				}, {
					scope: 'email,public_profile'
				});
			} catch(E) {
				showEmailLogin();
			}
		});
		return false;
	});
	if ($('#networkField').val() > 0 && $('#emailField').val().length) {
		$('#emailField').prop('readonly', true);
		showEmailLogin();
	}
});

if (typeof window.overrideModal === 'object') {
	var overrideBB = bootbox.dialog({
		title: 'Warning'._(),
		message: '<p>' + 'WARNING!<br>You are about to log-in as the LEADER of team %teamname%<br>Are you sure you want to do it?'._({teamname: window.overrideModal.team_name}) + '</p>',
		buttons: {
			default: {
				label: 'Login as a player'._(),
				className: 'btn-warning',
				callback: function(){
					$('#emailField').val(window.overrideModal.email);
					$('#activationField').val(window.overrideModal.activation_code);
					$('#activation-form').append('<input type="hidden" name="override" value="2">').submit();
				}
			},
			confirm: {
				label: 'Yes'._(),
				className: 'btn-success pull-right',
				callback: function(){
					$('#emailField').val(window.overrideModal.email);
					$('#activationField').val(window.overrideModal.activation_code);
					$('#activation-form').append('<input type="hidden" name="override" value="1">').submit();
				}
			},
			cancel: {
				label: 'Cancel'._(),
				className: 'btn-danger pull-left'
			}
		}
	});
}