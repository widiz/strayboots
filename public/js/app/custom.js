function setCookie(cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + Math.ceil(exdays * 864e5));
	document.cookie = cname + "=" + cvalue + "; expires=" + d.toUTCString() + "; domain=.strayboots.com; path=/";
}
function getCookie(cname) {
	var ca = document.cookie.split(';');
	cname = cname + '=';
	for(var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ')
			c = c.substring(1);
		if (c.indexOf(cname) === 0)
			return c.substring(cname.length, c.length);
	}
	return false;
}
function blackbox(message, className){
	var dialog = bootbox.dialog({
		message: '<div class="text-center">' + message + '<br><br><button class="subxbtn">' + 'OK'._() + '</button></div>'
	});
	dialog.find('.modal-content').addClass('blackbox' + (typeof className === 'string' ? ' ' + className : ''));
	dialog.find('.subxbtn').click(function(){
		dialog.modal('hide');
		return false;
	});
	dialog.init(function(){
		setTimeout(function(){
			dialog.modal('hide');
		}, 2e4);
	});
	return dialog;
}
(function(){
	var reg = /%(\w+)%/g,
		precompiled = [/&/g, /</g, />/g, /"/g, /'/g, /([^>\r\n]?)(\r\n|\n\r|\r|\n)/g];
	function toText(html){
		return (html + '')
			.replace(precompiled[0], "&#038;")
			.replace(precompiled[1], "&#060;")
			.replace(precompiled[2], "&#062;")
			.replace(precompiled[3], "&#034;")
			.replace(precompiled[4], "&#039;");
	}
	String.prototype._ = function(props) {
		var str = this.toString();
		if (typeof window.translate[str] === 'string')
			str = window.translate[str];
		if (typeof props !== 'object')
			return str;
		return str.replace(reg, function(match, expr) {
			return typeof props[expr] !== 'undefined' ? toText(props[expr]) : ('%' + expr + '%');
		});
	};
	window.translate = window.translate || {};
})();
$(function(){
	$('#logout-btn').click(function(){
		var $this = $(this);
		bootbox.confirm("Are you sure you want to exit the hunt?"._(), function(result){
			if (result)
				document.location.href = $this.attr('href');
		});
		return false;
	});

	var $timer = $('#end-timer');
	if ($timer.length) {
		var countTo = (new Date()).getTime() + ($timer.data('seconds') || 0) * 1e3,
			show20SecWarn = !getCookie('twentysecwarn' + window.appLoc.orderHunt),
			counted = false,
			drawTimer;
		$timer = $('a.endtimer-link');
		drawTimer = function(){
			var seconds = Math.ceil((countTo - (new Date()).getTime()) / 1e3);
			if (seconds > 0) {
				setTimeout(drawTimer, 1e3);
				if (show20SecWarn && seconds > 60) {
					if (typeof window.ncrHunt === 'boolean' && window.ncrHunt) {
						if (seconds < 1800) {
							show20SecWarn = false;
							blackbox("You've got 30 minutes left for your scavenger hunt! If you are not on your way to the Woodruff Arts Center Campus already, we suggest you skip to question number 26 at the Midtown MARTA Station at 10th Street, to snap a team photo and move closer to the finish line.", 'ncrblackbox');
							setCookie('twentysecwarn' + window.appLoc.orderHunt, 1, 1);
						}
					} else if (seconds < 1200) {
						show20SecWarn = false;
						blackbox("You've got 20 minutes left for your scavenger hunt.<br>Let's get things rolling!"._());
						setCookie('twentysecwarn' + window.appLoc.orderHunt, 1, 1);
					}
				}
			} else if (counted) {
				Xreload();
			}
			var hours = Math.floor(seconds / 3600);
			var minutes = Math.floor((seconds - (hours * 3600)) / 60),
				sec = seconds % 60;
			if (sec < 10) sec = '0' + sec;
			if (minutes < 10) minutes = '0' + minutes;
			if (hours < 10) hours = '0' + hours;
			else if (hours > 99) hours = 99;
			$timer.text(hours + ':' + minutes + ':' + sec);
			if (--seconds > 0)
				counted = true;
		};
		if (Math.ceil((countTo - (new Date()).getTime()) / 1e3) > 0)
			drawTimer();
	}

	if (!getCookie('welcomescreen')) {
		$('#welcome-screen a').click(function(){
			$('#welcome-screen').fadeOut();
			$('html').removeClass('welcomescreen');
			setCookie('welcomescreen', 1, 7);
			$(window).unbind('.wsc');
		});
		setTimeout(function(){
			var w = $('#welcome-screen').fadeIn();
			if (w.length === 0)
				return;
			$('html').addClass('welcomescreen');
			$(window).on('resize.wsc orientationchange.wsc', function(){
				if (w.height() === 0 || w.css('visibility') === 'hidden') {
					w.hide();
					$('html').removeClass('welcomescreen');
					$(window).unbind('.wsc');
				}
			}).trigger('resize');
		}, 800);
	}
	if (typeof firebase == 'object' && window.appLoc.orderHunt > 0 && window.appLoc.timeLeft > 0) {
		var fbdb = firebase.database(),
			_loadTime = window.loadTime.getTime(),
			state = 0, bonusQuestions = {}, intervals,
			bonusQTime = 120, bonusQAlertTimer = 10;

		var showBonusQTimer = function(time){
			if (!(time > 0))
				return;
			state = 1;
			var bqTimer = $('#bq-timer'),
				interval;
			if (bqTimer.length === 0) {
				bqTimer = $(document.createElement('div')).attr({
					id: 'bq-timer',
					role: 'dialog',
					tabindex: '-1'
				}).addClass('bootbox modal fade').append(
					'<div class="modal-dialog modal-lg">' +
						'<div class="modal-content">' +
							'<div class="modal-body">' +
								'<div class="bootbox-body">' +
									'<img src="/img/bq1.png" width="450" height="285">' +
									'<h2>' + 'Bonus Question'._() + '</h2>' +
									'<div class="progressbar">' +
										'<div></div>' +
									'</div>' +
									'<span class="sec"></span>' +
									'<h3>' + 'Are you ready?'._() + '</h3>' +
								'</div>' +
							'</div>' +
						'</div>' +
					'</div>'
				).appendTo('body');
				bqTimer.modal({
					backdrop: true,
					keyboard: false,
					show: false
				});
			} else if (bqTimer.hasClass('qbox')) {
				bqTimer.modal('hide');
				bqTimer.remove();
				return showBonusQTimer(time);
			}
			bqTimer.unbind('hidden.bs.modal').on('hidden.bs.modal', function(){
				clearInterval(interval);
				bqTimer.remove();
				interval = bqTimer = null;
			}).modal('show');
			var sec = bqTimer.find('.sec'),
				progress = bqTimer.find('.progressbar > div'),
				t = time;
			var updateBQTimer = function(){
				if (--t >= 0) {
					sec.text(t + ' sec');
					progress.width((100 - Math.ceil(t * 100 / time)) + '%');
				} else {
					bqTimer.modal('hide');
				}
			};
			interval = setInterval(updateBQTimer, 1e3);
			updateBQTimer();
		};

		var hideBonusQ = function(bNum){
			if (state == 0)
				return;
			if (typeof bNum == 'number' && typeof bonusQuestions[bNum] != 'undefined')
				delete bonusQuestions[bNum];
			else return;
			if (state == 2)
				document.location.href = '/play?bqa=1';
			$('#bonusground').fadeOut(200, function(){
				$(this).remove();
			});
			$('html').removeClass('hide-content');
			state = 0;
			//checkBonusQ();
		};

		var showBonusQScreen = function(bNum){
			var bq = bonusQuestions[bNum];
			state = 1;
			if (typeof bq != 'object') {
				bonusQuestions[bNum] = 1;
				return;
			}
			if (bq === null)
				return;
			state = 2;
			var bqScreen = $('#bonusground');
			if (bqScreen.length === 0) {
				bqScreen = $(
					'<div id="bonusground" class="content-wrapper">' +
						'<div class="container">' +
							'<div class="bonus-content">' +
								'<div class="inner-content">' +
									'<h2>' + 'Bonus Question'._() + '</h2>' +
									'<div class="question"></div>' +
									'<div id="bonus-form-wrapper" class="clearfix">' +
										'<form action="/play" id="bonus-form" autocomplete="off" method="post">' +
											'<input type="hidden" name="action" value="bonus">' +
											'<input type="hidden" name="bqid" id="bqid" value="">' +
											'<input type="text" id="bonusField" name="answer" value="" class="answer" required="required" placeholder="' + 'Answer'._() + '">' +
											'<input type="submit" value="' + 'Submit'._() + '" class="submit subxbtn">' +
										'</form>' +
										'<div class="pie"></div>' +
									'</div>' +
								'</div>' +
							'</div>' +
						'</div>' +
					'</div>'
				).appendTo('body > .main-section');
				bqScreen.find('form').submit(function(e){
					e.preventDefault();
					$.ajax({
						url: "/play",
						cache: false,
						type: 'POST',
						data: $(this).serializeArray(),
						success: function(data){
							if (typeof data == 'object') {
								if (data.reload === true) {
									document.location.href = '/play?bqa=1';
								} else if (typeof data.message == 'string' && data.message) {
									toastr.error(null, data.message);
								} else {
									this.error();
								}
							} else {
								this.error();
							}
						},
						error: function(){
							toastr.error(null, "Something went wrong, please try again"._());
						}
					});
					return false;
				});
			}
			bqScreen.fadeIn(250);
			$('html').addClass('hide-content');
			bqScreen.find('.question').html((bq[1] == 0 ? '<b>' + 'For %points% points'._({points: bq[2]}) + '</b><br><br>' : '<b>' + 'Win a Prize'._() + '</b><br><br>') + bq[3]);
			bqScreen.find('#bqid').val(bq[0]);
		};

		var showBonusQ = function(bNum, timePassed){
			if (!(timePassed >= 0 && timePassed < (bonusQTime + bonusQAlertTimer)))
				return;
			if (timePassed < bonusQAlertTimer) {
				var time = bonusQAlertTimer - timePassed;
				setTimeout(showBonusQScreen, time * 1e3, bNum);
				showBonusQTimer(time);
			} else {
				showBonusQScreen(bNum);
			}
		};

		var checkBonusQ = function(){
			if (state != 0)
				return;
			var timeLeft = window.appLoc.timeLeft - intervals[0] - Math.ceil(((new Date()).getTime() - _loadTime) / 1e3);
			if (!(timeLeft > 0))
				return;
			var timePassed = intervals[0] - timeLeft;
			//if (!(timePassed > intervals[1])) return;
			var bNum = Math.floor(timePassed / intervals[1]) - 1;
			timePassed = timePassed % intervals[1];
			timeLeft = bonusQTime + bonusQAlertTimer - timePassed;
			//console.log(bNum, timePassed, timeLeft);
			if (timeLeft > 0 && bNum >= 0) {
				//debugger;
				var hideTimeout = setTimeout(hideBonusQ, timeLeft * 1e3, bNum), bqFire, bqInterval;
				bqInterval = setInterval(function(){
					if (--timeLeft <= 0) {
						clearInterval(bqInterval);
						$('#bonusground .pie').remove();
					} else {
						var tp = bonusQTime - timeLeft;
						var percent = tp * 100 / bonusQTime, deg;
						if (percent < 50) {
							deg = (tp * 360 / bonusQTime) + 90;
							$('#bonusground .pie').css('background-image', 'linear-gradient(' + deg + 'deg, transparent 50%, #613E07 50%),linear-gradient(90deg, #613E07 50%, transparent 50%)');
						} else {
							deg = (tp * 360 / bonusQTime) - 90;
							$('#bonusground .pie').css('background-image', 'linear-gradient(' + deg + 'deg, transparent 50%, #F39C12 50%),linear-gradient(90deg, #613E07 50%, transparent 50%)');
						}
					}
				}, 1e3);
				if (typeof bonusQuestions[bNum] == 'undefined') {
					//debugger;
					bonusQuestions[bNum] = 0;
					bqFire = function(snapshot){
						var oldVal = bonusQuestions[bNum];
						bonusQuestions[bNum] = snapshot.val();
						if (!bonusQuestions[bNum]) {
							$('#bq-timer').modal('hide');
							hideBonusQ(bNum);
							try { clearTimeout(hideTimeout); } catch(E) {}
							try { fbdb.ref(window.FB_PREFIX + "bonusq/" + window.appLoc.orderHunt + '/' + bNum).off("value", bqFire); } catch(E) {}
						} else if (oldVal === 1) {
							showBonusQScreen(bNum);
						}
					};
					fbdb.ref(window.FB_PREFIX + "bonusq/" + window.appLoc.orderHunt + '/' + bNum).on("value", bqFire);
				}
				showBonusQ(bNum, timePassed);
				//return;
			} else {
				timeLeft = intervals[1] - timePassed;
				//console.log('left' + timeLeft);
				if (timeLeft > 60 && timeLeft < 300) {
					var cname = 'bqwarn' + window.appLoc.orderHunt + '_' + (bNum + 1);
					if (!getCookie(cname)) {
						//debugger;
						blackbox("Heads up, a bonus question is coming up soon..."._());
						setCookie(cname, 1, 1);
					}
				}
			}
		};

		fbdb.ref(window.FB_PREFIX + "hqbonusinterval/" + window.appLoc.orderHunt).once("value", function(snapshot){
			intervals = snapshot.val();
			if (typeof intervals == 'object' && intervals !== null && intervals.length == 2) {
				if ($('#bq-timer.qbox').length) {
					setTimeout(function(){
						setInterval(checkBonusQ, 1e3);
					}, 15e3);
				} else {
					setInterval(checkBonusQ, 1e3);
				}
			}
		});
	}

	function Xreload(){
		if (typeof document.location.reload == 'function')
			document.location.reload(true);
		else
			document.location.href = document.location.href;
	}

	var $window = $(window);
	$window.on('resize orientationchange', function(){
		$('#navbar').css('max-height', ($window.height() - 50) + 'px');
	}).trigger('resize');
	$('button.navbar-toggle').click(function(){
		$('html').toggleClass('menuflow', $(this).hasClass('collapsed'));
	});
});