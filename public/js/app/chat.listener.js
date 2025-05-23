(function($){

	if (typeof window.order_hunt_id != 'number')
		return;

	var _room = firebase.database().ref().child(window.FB_PREFIX + 'orderhuntchat/' + window.order_hunt_id),
		firstOne = true;
	function getMessage(m){
		if (firstOne) {
			firstOne = false;
		} else {
			$('.icn.chat').addClass('new-msg');
			if (window.isLeader)
				return _room.off('child_added', getMessage);
			try {
				var message = m.val();
				toastr.options.progressBar = false;
				toastr.options.timeOut = 0;
				toastr.options.extendedTimeOut = 0;
				toastr.options.onclick = function() { $(".chat").click(); };
				toastr.success('Message from %name%'._({name: message.cn || message.pname || ''}), message.content);
			} catch(E) { }
		}
	}
	_room.orderByChild('timestamp').limitToLast(1).on('child_added', getMessage);

})(jQuery);