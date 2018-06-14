$(function(){
	
	$(".select2-auto").select2({
		placeholder: "Please choose one",
		allowClear: true
	});

	/*$('.btn.sendmail').click(function(){
		var order_id = $(this).data('id');
		bootbox.dialog({
				title: "Send mail",
				message: '<div class="row">  ' +
					'<div class="col-md-12"> ' +
						'<form class="form-horizontal" onsubmit="return false"> ' +
							'<div class="form-group"> ' +
								'<label class="col-md-4 control-label" for="name">Email</label> ' +
								'<div class="col-md-6"> ' +
									'<input id="sendmail-email" name="email" type="email" placeholder="Your email" class="form-control input-md"> ' +
								'</div>' +
							'</div>' +
							'<div class="form-group"> ' +
								'<label class="col-md-4 control-label" for="sendto">Send to?</label> ' +
								'<div class="col-md-4">' +
									'<div class="radio">' +
										'<label for="sendto-0"> ' +
											'<input type="radio" name="sendto" id="sendto-0" value="test" checked> Me (testing)' +
										'</label> ' +
									'</div>' +
								'</div>' +
								'<div class="col-md-4">' +
									'<div class="radio">' +
										'<label for="sendto-1"> ' +
											'<input type="radio" name="sendto" id="sendto-1" value="client"> Client' +
										'</label>' +
									'</div>' +
								'</div>' +
							'</div>' +
						'</form>' +
					'</div>' +
				'</div>',
				buttons: {
					default: {
						label: "Cancel",
						className: "btn-default"
					},
					success: {
						label: "Send",
						className: "btn-success",
						callback: function(){
							var $email = $('#sendmail-email'),
								testing = $('#sendto-0').is(':checked');
							if (testing && !(/[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/.test($email.val()))) {
								toastr.error(null, 'Please enter a valid email');
								return false;
							}
							if (confirm("Are you sure?")) {
								$.post("/admin/orders/mail/" + order_id, {
									email: testing ? $email.val() : ''
								}, function(data){
									if (typeof data == 'object' && typeof data.success == 'object' && data.success.http_response_code == 200)
										toastr.success(null, "Email sent");
									else
										toastr.error(null, 'Unknown error occurred; please try again later');
								}, 'json');
							} else {
								return false;
							}
						}
					}
				}
			}
		);

		$('#sendto-0').change(function(){
			$('#sendmail-email').prop('disabled', !$(this).is(':checked'));
		});
		$('#sendto-1').change(function(){
			$('#sendmail-email').prop('disabled', $(this).is(':checked'));
		});

		return false;
	});*/

});