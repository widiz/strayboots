$('#playground').css('visibility', 'visible');
$(function(){
	if (window.isLeader && typeof window.answerLimit === 'number' && window.answerLimit > 2) {
		var canHint = $('#hint-form').length && !window.appLoc[2],
			dialog;
		if (canHint) {
			var dialog = bootbox.dialog({
				title: 'Last chance'._(),
				message: '<p>' + 'You got 1 last try! Would you like to take a hint?'._() + '</p>',
				buttons: {
					confirm: {
						label: 'Yes'._(),
						className: 'btn-success',
						callback: function(){
							setTimeout(function(){
								setCookie('wronghint' + window.appLoc[0], 1, 1);
								$('#hint-form a.btn').trigger('click');
							}, 300);
						}
					},
					cancel: {
						label: 'No'._(),
						className: 'btn-danger'
					}
				}
			});
		} else if (!getCookie('wronghint' + window.appLoc[0])) {
			var dialog = bootbox.dialog({
				title: 'Last chance'._(),
				message: '<p>' + 'You got 1 last try!'._() + '</p>',
				buttons: {
					cancel: {
						label: 'Close'._(),
						className: 'btn-default'
					}
				}
			});
		}
	}
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
	$('#bq-timer').modal('show');
	setTimeout(function(){
		$('html,body').animate({
			scrollTop: 0
		}, 200);
	}, 250);
	setTimeout(function(){
		$('html,body').animate({
			scrollTop: 0
		}, 200);
	}, 800);
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
	var $preground = $('.pmessagebox');
	if ($preground.length) {
		var $pBtn = $preground.find('a.btn.continue'),
			iTime = 5;
		$pBtn.append(' <span>' + iTime + '</span>')
		setTimeout(function(){
			$pBtn.removeClass('disabled').removeAttr('disabled').click(function(){
				$('#ack-form').submit();
				return false;
			}).find('span').hide();
		}, iTime * 1e3);
		for (var ii = iTime; ii > 0; ii--) {
			setTimeout(function(x){
				$pBtn.find('span').text(x);
			}, 1e3 * (iTime - ii), ii);
		}
	} else {
		$('#playground,#postground').addClass('show');
	}
	var $completionWrapper = $('#completion-wrapper');
	if ($completionWrapper.length) {
		var $inputs = $completionWrapper.find('input'),
			$answerField = $('#answerField');
		$completionWrapper.click(function(e){
			if (e.target.nodeName == 'INPUT') {
				e.preventDefault();
				e.target.focus();
			} else {
				var focusOn = false;
				if (e.target.nodeName == 'LI') {
					focusOn = $(e.target).find('input');
					if (focusOn.length === 0)
						focusOn = $inputs;
				} else if (!$inputs.is(':focus')) {
					focusOn = $inputs;
				}
				if (focusOn.length) {
					e.preventDefault();
					focusOn.get(0).focus();
				}
			}
		});
		$inputs.change(function(){
			$answerField.val($completionWrapper.find('li.letter').map(function(){
				var $this = $(this);
				var input = $this.find('input');
				return input.length ? input.val() : $this.text();
			}).toArray().join(''));
		});
		$inputs.focus(function(){
			if (this.value.length)
				this.setSelectionRange(0, this.value.length);
		});
		$inputs.keyup(function(){
			if (this.value.length > 1)
				this.value = this.value[this.value.length - 1];
			if (this.value.length === 1) {
				if (/[\s\t\n\r\v\f\b!@#\$%^&*_\?<>,`~=+\[\]\{\}'"\.\(\)\-\\\/]/.test(this.value)) {
					this.setSelectionRange(0, this.value.length);
				} else {
					var found = false, i;
					for (i = 0; i < $inputs.length; i++) {
						if (found) {
							$inputs.get(i).focus();
							return;
						} else if ($inputs.get(i).name == this.name) {
							found = true;
						}
					}
					for (i = 0; i < $inputs.length; i++) {
						var input = $inputs.get(i);
						if (input.value.length === 0) {
							input.focus();
							return;
						}
					}
					this.blur();
				}
			}
		});
		$('#answer-form-wrapper #main-form').submit(function(){
			var self = this,
				btn = $(this).find('.btn,input[type="submit"]');
			if (btn.hasClass('disabled'))
				return false;
			btn.addClass('disabled').prop('disabled', true);
			var k = $.inArray('', $inputs.map(function(){
				return this.value;
			}).toArray());
			if (k >= 0) {
				toastr.error(null, "Please fill all the characters"._());
				$inputs.get(k).focus();
				btn.removeClass('disabled').prop('disabled', false);
				return false;
			}
			$('#loading-indicator').fadeIn(220);
			setTimeout(function(){
				self.submit();
			}, 120);
			return false;
		});
	} else {
		$('#answer-form-wrapper form').submit(function(){
			var self = this,
				btn = $(this).find('.btn,input[type="submit"]');
			if (btn.hasClass('disabled'))
				return false;
			btn.addClass('disabled').prop('disabled', true);
			$('#loading-indicator').fadeIn(220);
			setTimeout(function(){
				self.submit();
			}, 120);
			return false;
		});
	}

	$('#skip-form a.btn').click(function(){
		if ($('.bootbox-confirm .btn-default').length)
			return false;
		$('body').focus();
		bootbox.confirm("Are you sure you want to skip? There's no way back..."._(), function(result){
			if (result)
				$('#skip-form').submit();
		}).on('shown.bs.modal',function(){
			$('.bootbox-confirm .btn-default').focus();
		});
		return false;
	});

	$('#hint-form a.btn').click(function(){
		if ($('.bootbox-confirm .btn-default').length)
			return false;
		$('body').focus();
		if ($(this).data('warn') > 0) {
			bootbox.confirm("Using a HINT will deduct half of the points. Are you sure?"._(), function(result){
				if (result)
					$('#hint-form').submit();
			}).on('shown.bs.modal',function(){
				$('.bootbox-confirm .btn-default').focus();
			});
		} else {
			$('#hint-form').submit();
		}
		return false;
	});


	if (typeof window.qtimeout == 'object') {
		var _loadTime = window.loadTime.getTime(),
			timeoutLeft = window.qtimeout[0],
			timeout = window.qtimeout[1],
			timeoutProgress = $('.timeout-progress').removeClass('hidden').children('div'),
			timeoutText = $('.timeout-progress time'),
			hintAvailable = $('#hint-form').length && !window.appLoc[2],
			intervalId;
		var timeoutFunc = function(){
			var timeLeft = Math.max(0, timeoutLeft - Math.ceil(((new Date()).getTime() - _loadTime) / 1e3));
			var tp = timeout - timeLeft;
			timeoutProgress.css('width', ((timeout - timeLeft) * 100 / timeout) + '%');
			timeoutText.text(secondsToClock(timeLeft, true));
			if (timeLeft === 0) {
				if ($('#skip-form').length === 0)
					Xreload();
				else
					$('#skip-form').append('<input type="hidden" name="autoskip" value="1">').submit();
				clearInterval(intervalId);
			} else if (hintAvailable && timeLeft <= (timeout / 2)) {
				//console.log('autohint');
				hintAvailable = false;
				$('#hint-form').submit();
			}
		};
		intervalId = setInterval(timeoutFunc, 1e3);
		timeoutFunc();
	}

	if (typeof firebase == 'object' && window.appLoc.timeLeft >= 0) {
		var fbdb = firebase.database(),
			redirectTimeout = null;
		setTimeout(function(){
			fbdb.ref(window.FB_PREFIX + 'orderhuntloc/' + window.appLoc[0]).on('value', function(snapshot) {
				var currentQuestion = snapshot.val();
				if (currentQuestion === null)
					return;
				if (currentQuestion === 99999 || currentQuestion === 'break' || currentQuestion === 'strategy') {
					Xreload();
					return;
				}
				currentQuestion = currentQuestion.split('_');
				if (currentQuestion[0] > window.appLoc[1] ||
					(currentQuestion[0] == window.appLoc[1] &&
						((currentQuestion[1] === '1') != window.appLoc[2] || (currentQuestion[2] === '1') != window.appLoc[3])))
					Xreload();
				else if (currentQuestion[0] != window.appLoc[1] && redirectTimeout === null)
					redirectTimeout = setTimeout(Xreload, 3e4);
			});
		}, 2e3);
	}

	if (typeof window.fbFunc == 'function') {
		window.fbFunc(function(){
			$('#shareblock').css({
				visibility: 'visible',
				display: 'block'
			}).fadeIn(200).find('a.share.facebook').click(function(){
				if (typeof window.shareInfo == 'object') {
					FB.ui({
						method: 'share',
						href: document.location.protocol + '//' + document.location.host + '/share.php?' + $.param({
							title: window.shareInfo.title || $preground.find('h2').first().text(),
							link: window.shareInfo.url || (document.location.protocol + '//www.strayboots.com/'),
							f: (window.shareInfo.image || '/img/bg-1.jpg').replace(/^\/?uploads\/[a-z]+\//, ''),
							//caption: window.shareInfo.caption || 'Strayboots Scavenger Hunts'._(),
							description: window.shareInfo.description || $preground.find('b.wyg').text()
						})
					});
				} else if (typeof window.shareInfoEnd == 'object') {
					FB.ui({
						method: 'share',
						href: document.location.protocol + '//' + document.location.host + '/share.php?' + $.param({
							title: window.shareInfoEnd.title || $preground.find('h2').first().text(),
							link: window.shareInfoEnd.url || (document.location.protocol + '//www.strayboots.com/'),
							f: (window.shareInfoEnd.image || '/img/bg-1.jpg').replace(/^\/?uploads\/[a-z]+\//, ''),
							//caption: window.shareInfoEnd.caption || 'Strayboots Scavenger Hunts'._(),
							description: window.shareInfoEnd.description || $preground.find('b.wyg').text()
						})
					});
				} else {
					FB.ui({
						method: 'share',
						href: document.location.protocol + '//' + document.location.host + '/share.php?' + $.param({
							title: 'We just successfully finished our Strayboots scavenger hunt!'._(),
							//link: 'https://www.strayboots.com/',
							//caption: 'Strayboots Scavenger Hunts'._(),
							description: 'That was so much fun!!! #teambuilding #scavengerhunt @strayboots'._()
						}),
					});
				}

				return false;
			});
		});
	} else {
		$('#shareblock').hide();
	}

	function Xreload(){
		if (typeof document.location.reload == 'function')
			document.location.reload(true);
		else
			document.location.href = document.location.href;
	}
});