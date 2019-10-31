$(function(){
	try {
		document.createEvent('TouchEvent');
	} catch (E) {
		$('#leaderboard,#custom-questions,#bonus-questions,#survey-results,#survey-results-txt').parent().niceScroll({
			cursorcolor: '#F39C12',
			touchbehavior: true,
			bouncescroll: false,
			grabcursorenabled: false,
			preventmultitouchscrolling: false,
			boxzoom: false,
			cursoropacitymin: 0.3,
			scriptpath:'/img/'
		});
	}
	if (typeof window.fbFunc == 'function') {
		$('.lightBoxGallery a .share').click(function(e){
			e.stopPropagation();
			var $a = $(this).closest('a');
			FB.ui({
				method: 'share',
				href: document.location.protocol + '//' + document.location.host + '/share.php?' + $.param({
					title: 'We just successfully finished our Strayboots scavenger hunt!',
					link: document.location.protocol + '//' + document.location.host + document.location.pathname + document.location.search + '#' + $a.attr('id'),
					f: ($a.attr('href') || '/img/11600.jpg').replace(/^\/?uploads\/[a-z]+\//, '').replace(/\.wm\.\d+\./, '.wm.'),
					//caption: 'Strayboots Scavenger Hunts',
					description: 'That was so much fun!!! #teambuilding #scavengerhunt @strayboots'
				})
			});
			return false;
		});
		$('.share-content a').click(function(e){
			e.preventDefault();
			FB.ui({
				method: 'share',
				href: document.location.protocol + '//' + document.location.host + '/share.php?' + $.param({
					title: 'We just successfully finished our Strayboots scavenger hunt!',
					link: document.location.protocol + '//' + document.location.host + document.location.pathname + document.location.search,
					//f: $a.find('img').attr('src').replace('.thumbnail', '.wm'),
					//caption: 'Strayboots Scavenger Hunts',
					description: 'That was so much fun!!! #teambuilding #scavengerhunt @strayboots'
				})
			});
			return false;
		});
	} else {
		$('.share-section').slideUp(150);
	}

	var $images = $('.imgbox[data-team]');
	$('#team-filter select').change(function(){
		if (this.value === '')
			$images.show();
		else
			$images.hide().filter('[data-team="' + this.value + '"]').show();
	});

	if (document.location.hash && document.location.hash.substr(0, 2) == '#x')
		$(document.location.hash).trigger('click');

	var $gallery = $('#blueimp-gallery');
	$('#team-filter .play').click(function(){
		var $a = $images.filter(':visible').first().find('a');
		if ($a.length > 0) {
			$gallery.unbind('slidecomplete.kk').one('slidecomplete.kk', function(event){
				setTimeout(function(){
					$gallery.data('gallery').play(1500);
				}, 200);
			});
			$a.trigger('click');
		}
	});

	$(".del-chat-img").click(function() {
		var image = $(this).data('url');
		$.ajax({
			type: "POST",
			url: '/admin/order_hunts/delImage',
			data: {
				'img': image,
			},
			success: function(data) {
				if (typeof data === 'object' && data && data.success) {
					location.reload();
				}
			},
			error: function(error){

			}
		});
	});
});