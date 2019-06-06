$(function(){
	$('#activation-form').validate();

	var isTeam = false,
		eventId = null,
		lock = false;
	$('#activation-wrapper a[data-val]').click(function(){
		isTeam = $(this).data('val') === 'team';
		$('#event').trigger('change');
		$('.steps-wrapper > .step2-' + (isTeam ? 'team' : 'player')).stop().slideDown().siblings('.step').stop().slideUp();
		return false;
	});
	$('#choose-back').click(function(){
		$('.steps-wrapper > .step1').stop().slideDown().siblings('.step').stop().slideUp();
		return false;
	});
	$('#code-activation').click(function(){
		$('.steps-wrapper > .step3').stop().slideDown().siblings('.step').stop().slideUp();
		return false;
	});
	$('#add-player').click(function(){
		if ($('#players-list .row.player').length < 7) {
			$(
				'<div class="row player" style="display:none">' +
					'<div class="col-sm-12">' +
						'<input type="text" name="name' + Math.floor(Math.random() * 1e6) + '" class="answer" required="required" placeholder="' + 'Team Member Name'._() + '">' +
						//'<input type="tel" name="phone' + Math.floor(Math.random() * 1e6) + '" class="answer" required="required" placeholder="' + 'Team Member Phone Number'._() + '">' +
						'<input type="text" name="id' + Math.floor(Math.random() * 1e6) + '" class="answer" required="required" placeholder="' + 'Team Member ID Number'._() + '">' +
						'<input type="email" name="email' + Math.floor(Math.random() * 1e6) + '" class="answer" placeholder="' + 'Team Member Email'._() + '">' +
						'<a class="btn btn-danger" href="javascript:;"><i class="fa fa-minus"></i></a>' +
					'</div>' +
				'</div>'
			).appendTo('#players-list').slideDown(150);
		} else {
			alert('Players limit reached; up to 8 per team'._());
		}
		return false;
	});
	$('#players-list').on('click', '.btn-danger', function(){
		$(this).closest('.row.player').slideUp(150, function(){
			$(this).remove();
		})
		return false;
	});
	$('#player-reg').validate({
		submitHandler: function(form) {
			if (lock)
				return;
			lock = true;
			$('#loading-indicator').stop().fadeIn(200);
			$.ajax({
				url: '/ncr/saudi',
				type: 'POST',
				data: {
					eventId: $('#eventId').val(),
					leader: $('#playerEmailField').val(),
					leaderPhone: $('#playerPhoneField').val(),
					leaderId: $('#playerIDField').val(),
					leaderName: $('#playerNameField').val()
				},
				success: function(data){
					if (typeof data === 'object' && data !== null && data.success) {
						if (data.activation) {
							$('#code-activation').trigger('click');
							$('#activationField').val(data.activation);
							$('#activationEmail').val($('#playerEmailField').val());
							$('#activation-form').submit();
						}
						$('.steps-wrapper > .step4').stop().slideDown().siblings('.step').stop().slideUp();
						$('#loading-indicator').stop().fadeOut(200);
						setTimeout(function(){
							lock = false;
						}, 300);
					} else {
						this.error(data);
					}
				},
				error: function(data){
					var error = typeof data === 'string' && data ? data : 'unknown error occurred; please contact support';
					if (typeof data === 'object' && data !== null && typeof data.error === 'string' && data.error)
						error = data.error;
					$('#loading-indicator').stop().fadeOut(200);
					bootbox.alert(error);
					lock = false;
				}
			});
		}
	});
	$('#team-reg').validate({
		submitHandler: function(form) {
			if (lock)
				return;
			lock = true;
			$('#loading-indicator').stop().fadeIn(200);
			$.ajax({
				url: '/ncr/saudi',
				type: 'POST',
				data: {
					eventId: $('#eventId').val(),
					leader: $('#leaderEmailField').val(),
					leaderPhone: $('#leaderPhoneField').val(),
					leaderId: $('#leaderIDField').val(),
					leaderName: $('#leaderNameField').val(),
					players: JSON.stringify($('#players-list input[type="email"]').map(function(){
						return [[
							this.value,
							$(this).siblings('input[name^="name"]').val() || '',
							$(this).siblings('input[name^="phone"]').val() || '',
							$(this).siblings('input[name^="id"]').val() || ''
						]];
					}).toArray())
				},
				success: function(data){
					if (typeof data === 'object' && data !== null && data.success) {
						if (data.activation) {
							$('#code-activation').trigger('click');
							$('#activationField').val(data.activation);
							$('#activationEmail').val($('#leaderEmailField').val());
							$('#activation-form').submit();
						}
						$('.steps-wrapper > .step4').stop().slideDown().siblings('.step').stop().slideUp();
						$('#loading-indicator').stop().fadeOut(200);
						setTimeout(function(){
							lock = false;
						}, 300);
					} else {
						this.error(data);
					}
				},
				error: function(data){
					var error = typeof data === 'string' && data ? data : 'unknown error occurred; please contact support';
					if (typeof data === 'object' && data !== null && typeof data.error === 'string' && data.error)
						error = data.error;
					$('#loading-indicator').stop().fadeOut(200);
					bootbox.alert(error);
					lock = false;
				}
			});
		}
	});
	try {
		setCookie('welcomescreen', 1, 14);
	} catch(E) { }
});