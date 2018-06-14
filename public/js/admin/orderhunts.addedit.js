$(function(){
	
	$(".select2-auto").select2({
		placeholder: "Please choose one",
		allowClear: true
	});

	$('.datetimepicker').datetimepicker({
		//format: 'YYYY-MM-DD HH:mm'
	});

	var $expire = $('#fieldExpire');
	$('#fieldStart').blur(function(){
		$expire.val(moment(this.value).add(1, 'week').format("L LT"));
	});
	/*$('#fieldMultiHunt').change(function(){
		if ($(this).is(':checked'))
			$('#fieldDurationFinish').prop('checked', true);
	});*/

});