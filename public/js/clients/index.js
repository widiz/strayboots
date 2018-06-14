$(function(){
	$.ajaxSetup({
		cache: false
	});
	$('.orderhunt-box .ibox-tools .collapse-link').one('click', function(){
		var $this = $(this);
		var $huntbox = $this.closest('div.orderhunt-box');
		$.get("/clients/order_hunts/summary/" + $huntbox.data('id'), function(html){
			var $content = $huntbox.children('.ibox-content').html(html.replace(/ibox-content clearfix/g, 'row')).css('background-image', 'none');
			$content.find('.blueimp-gallery').remove();
			//blueimp.Gallery($content.find('.lightBoxGallery a'));
			setTimeout(function(){
				$huntbox.resize();
				if (typeof window.processLeaderMaps == 'function')
					window.processLeaderMaps();
			}, 50);
		});
	});
});