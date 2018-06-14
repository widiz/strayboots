$(function(){
	$('#activation-form').validate();

	var isTeam = false,
		eventId = null,
		lock = false;
	$('#activation-wrapper a[data-val]').click(function(){
		isTeam = $(this).data('val') === 'team';
		$('#event').trigger('change');
		$('.steps-wrapper > .step2').stop().slideDown().siblings('.step').stop().slideUp();
		return false;
	});
	$('#activation-wrapper a[data-val]').click(function(){
		$('.steps-wrapper > .step2').stop().slideDown().siblings('.step').stop().slideUp();
	});
	$('#event').change(function(){
		var soldout = $('#event option:selected').data('soldout');
		$('#choose-event').toggleClass('disabled', !(this.value && !soldout));
		$('.soldout').toggleClass('hidden', !(this.value && soldout));
	});
	$('#choose-back').click(function(){
		$('.steps-wrapper > .step1').stop().slideDown().siblings('.step').stop().slideUp();
		return false;
	});
	$('#choose-event').click(function(){
		var id = parseInt($('#event').val() || 0);
		if ($('#event option:selected').data('soldout') || !(id > 0))
			return false;
		eventId = id;
		$('.steps-wrapper > .step3-' + (isTeam ? 'team' : 'player')).stop().slideDown().siblings('.step').stop().slideUp();
		return false;
	});
	$('#add-player').click(function(){
		if ($('#players-list .row.player').length < 7) {
			$(
				'<div class="row player" style="display:none">' +
					'<div class="col-sm-12">' +
						'<input type="text" name="name' + Math.floor(Math.random() * 1e6) + '" class="answer" placeholder="Team Member Name">' +
						'<input type="email" name="email' + Math.floor(Math.random() * 1e6) + '" class="answer" required="required" placeholder="Team Member Email">' +
						'<a class="btn btn-danger" href="javascript:;"><i class="fa fa-minus"></i></a>' +
					'</div>' +
				'</div>'
			).appendTo('#players-list').slideDown(150);
		} else {
			alert('Players limit reached; up to 8 per team');
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
				url: '/ncr/ajax',
				type: 'POST',
				data: {
					eventId: eventId,
					leader: $('#playerEmailField').val(),
					leaderName: $('#playerNameField').val()
				},
				success: function(data){
					if (typeof data === 'object' && data !== null && data.success) {
						if (data.activation)
							$('.activation-block').show().find('.code').text(data.activation);
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
				url: '/ncr/ajax',
				type: 'POST',
				data: {
					eventId: eventId,
					leader: $('#leaderEmailField').val(),
					leaderName: $('#leaderNameField').val(),
					players: JSON.stringify($('#players-list input[type="email"]').map(function(){
						return [[this.value, $(this).siblings('input[type="text"]').val() || '']];
					}).toArray())
				},
				success: function(data){
					if (typeof data === 'object' && data !== null && data.success) {
						if (data.activation)
							$('.activation-block').show().find('.code').text(data.activation);
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
});