(function($){

	function Xreload() {
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
				if (currentQuestion !== 'strategy' && redirectTimeout === null) {
					redirectTimeout = 1;
					Xreload();
				}
			});
		}, 3e3);
	}

})(jQuery);