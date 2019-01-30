$(function(){

	//$('.colorpicker').colorpicker();
	var removedImages = [];

	$('#reset-all').click(function(){
		if (confirm("Are you sure?")) {
			$('.img-preview a').trigger('click', [1]);
			$('.customize-form textarea, .customize-form input[type="text"], .customize-form input[type="file"]').val('');
		}
		return false;
	});

	var imgPreview = $('.img-preview[data-image]').each(function(){
		var $this = $(this);
		var url = $this.data('image').replace('/cb', '/cb' + Math.floor(Math.random() * 1e4)) + '?cb=' + Math.floor(Math.random() * 1e5),
			img = new Image();
		$(img).load(function(){
			$this.addClass('hasimg onserver').css('background-image', 'url(' + url + ')');
		});
		img.src = url;
	});
	imgPreview.find('a').click(function(){
		if (arguments.length === 2 || confirm('Are you sure?')) {
			var imgP = $(this).parent().removeClass('hasimg').css('background-image', 'none');
			var input = $('div:nth-child(' + (imgP.parent().index() + 1) + ') > input.upload');
			if (imgP.hasClass('onserver')) {
				imgP.removeClass('onserver');
				removedImages.push(input.attr('name'));
				$('#fieldRemovedImages').val(removedImages.join(','));
			}
			input.replaceWith(input.val('').clone(true));
		}
		return false;
	});

	var $tmpForm = $('<form action="/index/customPreview" method="post" target="pframe" style="display:none"></form>').appendTo('body');
	$('#preview').click(function(){
		$tmpForm.empty();
		$.each($('.customize-form').serializeArray(), function(n, v){
			v.type = 'hidden';
			$(document.createElement('input')).attr(v).appendTo($tmpForm);
		});
		$tmpForm.submit();
		setTimeout(function(){
			$('input.btn-default[type="submit"]').prop('disabled', false);
		}, 250);
		return false;
	}).trigger('click');
	
	var sendToFrame = false;
	var $pframe = $('#pframe').load(function(){
		sendToFrame = true;
		$('input.upload').each(function(){
			readURL.apply(this);
		});
		setTimeout(function(){
			sendToFrame = false;
		}, 500);
	});
	
	function readURL() {
		var $this = $(this);
		if (this.files && this.files[0]) {
			var reader = new FileReader();
			reader.onload = function(e){
				var id = $this.parent().index();
				$('div:nth-child(' + (id + 1) + ') > .img-preview').addClass('hasimg').css('background-image', 'url('  + e.target.result + ')');
				if (sendToFrame) {
					$pframe.get(0).contentWindow.postMessage(JSON.stringify({
						type: 'image',
						id: id,
						data: e.target.result
					}), '*');
				}
			};
			reader.readAsDataURL(this.files[0]);
		}
	}
	
	$('input.upload').change(function(){
		readURL.apply(this);
		$('input.btn-default[type="submit"]').prop('disabled', true);
	});

	$('#fieldCustomCSS,#fieldHeaderColor,#fieldBackgroundColor,#fieldMainColor,#fieldSecondColor').on('change keyup changeColor', function(){
		$('input.btn-default[type="submit"]').prop('disabled', true);
	});

	$('.customize-form').submit(function(e){
		e.preventDefault();
		var self = this;
		bootbox.confirm("Did you verify that all texts are visible correctly after your change?<br>Please make sure that the colors you've selected oppose the background colors! We don't want it to affect your experience.<br>If you wish to reset the design, please make sure to hit the RESET button, and then the SAVE button.", function(result) {
			if (result)
				self.submit();
		}).addClass('flex').css({
			'align-items': 'center',
			'justify-content': 'center',
			'font-size': '20px'
		});
		return false;
	});

});