$(function(){
	var $timer = $('#start-timer');
	if ($timer.length) {
		var countTo = (new Date()).getTime() + ($timer.data('seconds') || 0) * 1e3,
			drawTimer;
		drawTimer = function(){
			var seconds = Math.ceil((countTo - (new Date()).getTime()) / 1e3);
			if (seconds > 0) {
				setTimeout(drawTimer, 1e3);
			} else {
				if ($('.timerbtn').prop('disabled', false).fadeIn(300).removeClass('hidden').length === 0)
					setTimeout(Xreload, 1e3);
			}
			$timer.text(secondsToClock(seconds, false, true));
		};
		if (Math.ceil((countTo - (new Date()).getTime()) / 1e3) > 0) {
			drawTimer();
		} else {
			if ($('.timerbtn').prop('disabled', false).fadeIn(200).removeClass('hidden').length === 0)
				Xreload();
		}
	}
	var $name = $('#fieldName').focus(function(){
		var $this = $(this);
		if ($this.data('focusclear') == 1)
			$this.data('focusclear', 0).val('');
	});
	var formLock = false;
	$('#name-form').submit(function(e){
		if (formLock)
			return false;
		var ok = true;
		var name = $name.val().trim();
		if (name == $name.data('default')) {
			toastr.error(null, "Pick a funky name for your team and let's win this hunt!"._());
			ok = false;
		}
		if (name.length < 2) {
			toastr.error(null, "Name is too short"._());
			ok = false;
		}
		if (name.length > 30) {
			toastr.error(null, "Name is too long"._());
			ok = false;
		}
		if (ok) {
			formLock = true;
			return true;
		} else {
			e.preventDefault();
			$name.focus();
			return false;
		}
	});
	$('.nameteam').click(function(){
		$('#name-form').trigger('submit');
	}).removeAttr('disabled');

	function secondsToClock(seconds, hideHoursOnZero, showDays) {
		var hours = Math.floor(seconds / 3600);
		var minutes = Math.floor((seconds - (hours * 3600)) / 60),
			sec = seconds % 60;
		if (sec < 10) sec = '0' + sec;
		if (minutes < 10) minutes = '0' + minutes;
		if (hours < 10) hours = '0' + hours;
		var days = '';
		if (typeof showDays == 'boolean' && showDays && hours > 23) {
			days = Math.floor(hours / 24) + ' ' + 'days'._() + ' ';
			hours = hours % 24;
		} else if (hours > 99) hours = 99;
		return days + (typeof hideHoursOnZero == 'boolean' && hideHoursOnZero && hours === '00' ? '' : (hours + ':')) + minutes + ':' + sec;
	}
	function Xreload(){
		if (typeof document.location.reload == 'function')
			document.location.reload(true);
		else
			document.location.href = document.location.href;
	}

	if (typeof firebase == 'object' && window.appLoc.timeLeft >= 0) {
		var fbdb = firebase.database(),
			redirectTimeout = null;
		setTimeout(function(){
			fbdb.ref(window.FB_PREFIX + 'orderhuntloc/' + window.appLoc[0]).on('value', function(snapshot) {
				var currentQuestion = snapshot.val();
				if (currentQuestion === null)
					return;
				if (currentQuestion.length && redirectTimeout === null) {
					redirectTimeout = 1;
					Xreload();
				}
			});
		}, 5e3);
	}
});