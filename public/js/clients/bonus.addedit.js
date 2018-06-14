$(function(){

	$('#fieldType').change(function(){
		$('#fieldScore').closest('.form-group').toggle(this.value != 1);
	}).trigger('change');

});