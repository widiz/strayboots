(function($){

	function Xreload() {
		if (typeof document.location.reload == 'function')
			document.location.reload(true);
		else
			document.location.href = document.location.href;
	}

	var showCountdown,
		gcounter = 30;
	try {
		gcounter = parseInt($('#cdown').text());
	} catch(E) {}
	showCountdown = function() {
		showCountdown = function(){};
		var count = gcounter,
			iv;
		$('.waitingscreen').addClass('hidden');
		$('.countdownscreen').removeClass('hidden');
		var $c = $('#cdown').text(count);
		iv = setInterval(function(){
			if (--count === 0) {
				clearInterval(iv);
				Xreload();
			}
			$c.text(count);
		}, 1e3);
	}

	if (typeof window.order_hunt_id !== 'number')
		return;

	if (typeof firebase !== 'object')
		return showCountdown();

	setTimeout(function(){
		var $leaderboard = $('#leaderboard');
		firebase.database().ref(window.FB_PREFIX + 'breakfb/' + window.order_hunt_id).on('value', function(snapshot){
			snapshot = snapshot.val();
			if (typeof snapshot == 'object' && snapshot !== null) {
				/*if (snapshot.length === 0 || (snapshot.length === 1 && snapshot[0] === 0)) {
					showCountdown();
				} else {*/
					$.get('/play', function(html){
						var $html = $(html);
						var $lb = $html.find('#leaderboard').html(),
							$cdown = $html.find('#cdown').html();
						$leaderboard.html($lb || '');
						try {
							if ($cdown > 0)
								gcounter = parseInt($cdown);
						} catch(E) {}
						if ($html.find('.waitingscreen.hidden').length === 1)
							showCountdown();
						else if ($leaderboard.html() === '')
							Xreload();
					});
					/*$leaderboard.children().addClass('green');
					var noTeams = true;
					for (var i = 0; i < snapshot.length; i++) {
						if (typeof snapshot[i][0] === 'number' && typeof snapshot[i][1] === 'number') {
							var $team = $leaderboard.children('[data-team="' + snapshot[i][0] + '"]');
							if ($team.length) {
								noTeams = false;
								$team.removeClass('green').find('.num').html(snapshot[i][1] < 10 ? '&nbsp' + snapshot[i][1] + '&nbsp' : snapshot[i][1] + '');
							} else {
								noTeams = true;
								break;
							}
						}
					}
					if (noTeams)
						showCountdown();*/
				//}
			}
		});
	}, 5e3);

})(jQuery);