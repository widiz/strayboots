$(function(){
	$('.navbar-fixed-bottom a, .navbar-fixed-top a').attr('href', 'javascript:;');
});
window.addEventListener("message", function(event){
	var origin = event.origin || event.originalEvent.origin;
	if (origin.indexOf('strayboots.com') >= 0) {
		try {
			var e = JSON.parse(event.data);
			if (e.type == 'image' && e.data) {
				switch (e.id) {
					case 0:
						$('.logo img').attr('src', e.data);
						break;
					case 1:
						$('.navbar-default').css('cssText', 'background-image: url(' + e.data + ') !important');
						break;
					case 2:
						$('body').css('cssText', 'background-image: url(' + e.data + ') !important');
						break;
					default:
				}
			}
		} catch(E) {}
	}
}, false);