jQuery(function($){

	$('.datetimepicker').datetimepicker({
		//format: 'YYYY-MM-DD HH:mm'
	});

	var $finish = $('#fieldFinish');
	$('#fieldStart').blur(function(){
		$finish.val(moment(this.value).add(2, 'hours').format("L LT"));
	});

	$('#fieldPay').change(function(){
		$('#paypalbtn').toggle(this.value == 0).siblings('input').toggleClass('hidden', this.value == 0);
	}).trigger('change');

	var $fieldHuntId = $("#fieldHuntId");
	var oldVal = $fieldHuntId.val();
	$('#fieldCityId').select2({
		placeholder: "Please choose a city"
	}).change(function(){
		$fieldHuntId.find('option').remove();
		$fieldHuntId.select2({
			data: typeof window.cityHunts[this.value] === 'object' ? window.cityHunts[this.value].slice() : [],
			placeholder: "Please choose a hunt"
		}).select2('val', '');
	}).trigger('change');
	$fieldHuntId.val(oldVal).trigger('change');

	function getPricePerPlayer(players) {
		var ppp = window.pricePerPlayer[Object.keys(window.pricePerPlayer)[0]];
		for (var i in window.pricePerPlayer) {
			if (players >= parseInt(i))
				ppp = window.pricePerPlayer[i];
			else
				break;
		}
		return ppp;
	}

	$('#fieldMaxPlayers').on('change keyup', function(){
		var players = parseInt(this.value);
		if (/^\d+$/.test('' + players) && players > 0 && $('#fieldHuntId').val() > 0) {
			var pb = $('.paypalblock').stop().fadeIn(),
				amount = Math.max(650, Math.max(15, players) * getPricePerPlayer(players)),
				amountCalc = Math.max(0, amount * 0.8);
			var before = pb.find('.beforep').text('$' + amount);
			before.parent().css('visibility', amount > amountCalc ? 'visible' : 'hidden');
			pb.find('.afterp').text('$' + amountCalc);
		} else {
			$('.paypalblock').stop().fadeOut(200);
		}
	}).trigger('change');
	$('#fieldHuntId').change(function(){
		var data = $(this).select2('data');
		if (typeof data === 'object' && data.length && typeof data[0] === 'object' && data[0].max_teams) {
			$('#uptotext').removeClass('hidden').find('b').text(data[0].max_teams);
			$('#fieldMaxTeams').attr('max', data[0].max_teams);
		} else {
			$('#uptotext').addClass('hidden');
		}
		$('#fieldMaxPlayers').trigger('change');
	}).trigger('change');
	$('#paypalbtn').click(function(){
		$('#savebtn').click();
		return false;
	});
	$('ul.faq li').click(function(){
		var $this = $(this);
		if ($this.hasClass('active')) {
			$this.removeClass('active').find('p').stop().slideUp();
		} else {
			$this.addClass('active').find('p').stop().slideDown();
		}
	}).filter('.loadmore').unbind().click(function(){
		$(this).empty().css('cursor', 'default').parent().find('li.h').slideDown(300).last();
	});
});