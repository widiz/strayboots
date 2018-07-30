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
			try {
				console.log(m.val());
				toastr.success('Message from %name%'._({name: m.val().pname}));
			} catch(E) { }
			//_room.off('child_added', getMessage);
		}
	}
	_room.orderByChild('timestamp').limitToLast(1).on('child_added', getMessage);

})(jQuery);