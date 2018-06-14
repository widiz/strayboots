$(function(){

	function quoteattr(s, preserveCR) {
		preserveCR = preserveCR ? '&#13;' : '\n';
		return ('' + s) /* Forces the conversion to string. */
			.replace(/&/g, '&amp;') /* This MUST be the 1st replacement. */
			.replace(/'/g, '&apos;') /* The 4 other predefined entities, required. */
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			/*
			You may add other replacements here for HTML only 
			(but it's not necessary).
			Or for XML, only if the named entities are defined in its DTD.
			*/ 
			.replace(/\r\n/g, preserveCR) /* Must be before the next replacement. */
			.replace(/[\r\n]/g, preserveCR);
			;
	}

	function handleSendPE(){
		var $this = $(this);
		var id = $this.data('id'),
			start = $this.data('start'),
			order = quoteattr($this.data('order')),
			client = quoteattr($this.data('client')),
			title = quoteattr($this.data('title'));
		bootbox.dialog({
			title: "Send post event mail",
			message: '<div class="row">  ' +
				'<div class="col-md-12"> ' +
					'<div>Client: ' + client + '</div>' +
					'<div>Order: ' + order + '</div>' +
					'<div>Hunt: ' + title + '</div>' +
					'<div>Start: ' + start + '</div><br>' +
					'<form class="form-horizontal" onsubmit="return false"> ' +
						'<div class="form-group"> ' +
							'<label class="col-md-4 control-label" for="name">Email</label> ' +
							'<div class="col-md-6"> ' +
								'<input id="sendmail-email" name="email" type="email" placeholder="Your email" class="form-control input-md"> ' +
							'</div>' +
						'</div>' +
						'<div class="form-group"> ' +
							'<label class="col-md-4 control-label" for="sendto">Send to?</label> ' +
							'<div class="col-md-4">' +
								'<div class="radio">' +
									'<label for="sendto-0"> ' +
										'<input type="radio" name="sendto" id="sendto-0" value="test" checked> Me (testing)' +
									'</label> ' +
								'</div>' +
							'</div>' +
							'<div class="col-md-4">' +
								'<div class="radio">' +
									'<label for="sendto-1"> ' +
										'<input type="radio" name="sendto" id="sendto-1" value="client"> Client' +
									'</label>' +
								'</div>' +
							'</div>' +
						'</div>' +
					'</form>' +
				'</div>' +
			'</div>',
			buttons: {
				default: {
					label: "Cancel",
					className: "btn-default"
				},
				success: {
					label: "Send",
					className: "btn-success",
					callback: function(){
						var $email = $('#sendmail-email'),
							testing = $('#sendto-0').is(':checked');
						if (testing && !(/[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/.test($email.val()))) {
							toastr.error(null, 'Please enter a valid email');
							return false;
						}
						if (confirm("Are you sure?")) {
							$.post("/admin/order_hunts/sendPostEvent/", {
								id: id,
								email: testing ? $email.val() : ''
							}, function(data){
								if (typeof data == 'object' && data.success === true)
									toastr.success(null, "Email sent");
								else
									toastr.error(null, 'Unknown error occurred; please try again later');
							}, 'json');
						} else {
							return false;
						}
					}
				}
			}
		});

		$('#sendto-0').change(function(){
			$('#sendmail-email').prop('disabled', !$(this).is(':checked'));
		});
		$('#sendto-1').change(function(){
			$('#sendmail-email').prop('disabled', $(this).is(':checked'));
		});

		return false;
	}

	$('.btn.sendpe').click(handleSendPE);
	$('.ibox-content,.index-table').on('click', '.btn.sendpe', handleSendPE);

	function handleBreakForce(){
		var $this = $(this);
		var id = $this.data('id'),
			bp = $this.data('bp');
		bootbox.dialog({
			title: "Force break release",
			message: '<div class="row">  ' +
				'<div class="col-md-12"> ' +
					'Are you sure?' +
				'</div>' +
			'</div>',
			buttons: {
				default: {
					label: "Cancel",
					className: "btn-default"
				},
				success: {
					label: "Force",
					className: "btn-success",
					callback: function(){
						$.post("/admin/order_hunts/breakForce/" + id, {
							bp: bp
						}, function(data){
							if (typeof data == 'object' && data.success === true) {
								toastr.success(null, "Done");
								$this.closest('.row').slideUp();
							} else {
								toastr.error(null, 'Unknown error occurred; please try again later');
							}
						}, 'json');
					}
				}
			}
		});
		return false;
	}

	$('.btn.forcebreak').click(handleBreakForce);
	$('.ibox-content,.index-table').on('click', '.btn.forcebreak', handleBreakForce);

	function handleLogsActionChange(){
		var $this = $(this).hide();
		var id = $this.data('id'),
			action = $this.data('action');

		var $edit = $this.siblings('.editx');
		if ($edit.length !== 0) {
			$edit.show().find('select').val(action);
			return false;
		}

		$edit = $(
			'<div class="editx" style="max-width:300px">' +
				'<div class="input-group">' +
					'<select class="form-control input-sm" style="padding:3px 8px;width:auto">' +
						'<option value="0"' + (action == 0 ? ' selected' : '') + '>Answered</option>' + 
						'<option value="1"' + (action == 1 ? ' selected' : '') + '>Answered With Hint</option>' + 
						'<option value="2"' + (action == 2 ? ' selected' : '') + '>Skipped</option>' + 
					'</select>' +
					'<span class="input-group-addon">' +
						'<a href="javascript:;">Save</a>' +
					'</span>' +
					'<span class="input-group-addon">' +
						'<a href="javascript:;" class="cancel">Cancel</a>' +
					'</span>' +
				'</div>' +
			'</div>'
		).insertAfter($this);

		$edit.find('a').click(function(){
			var $t = $(this);
			var $s = $edit.find('select');
			var newaction = $s.val();
			if ($t.is('.cancel') || newaction == action) {
				$edit.hide();
				$this.show();
				return false;
			}
			if (confirm("Are you sure?")) {
				$.post("/admin/order_hunts/updateAnswer/" + id, {
					action: newaction
				}, function(data){
					if (typeof data == 'object' && data.success === true) {
						toastr.success(null, "Action updated; please refresh");
						$edit.hide();
						$this.data('action', newaction).text($s.find('option:selected').text()).show();
						action = newaction;
					} else {
						toastr.error(null, 'Unknown error occurred; please try again later');
					}
				}, 'json');
			}
			return false;
		});

		return false;
	}

	$('#logs a.actionchange').click(handleLogsActionChange);
	$('.ibox-content,.index-table').on('click', 'a.actionchange', handleLogsActionChange);

	$('#leaderboard a.actionchange').click(function(){
		var $this = $(this).hide();
		var id = $this.data('id'),
			route = $this.data('route');

		var $edit = $this.siblings('.editx');
		if ($edit.length !== 0) {
			$edit.show().find('select').val(route);
			return false;
		}

		$edit = $(
			'<div class="editx" style="max-width:300px">' +
				'<div class="input-group">' +
					'<select class="form-control input-sm" style="padding:3px 8px;width:auto"></select>' +
					'<span class="input-group-addon">' +
						'<a href="javascript:;">Save</a>' +
					'</span>' +
					'<span class="input-group-addon">' +
						'<a href="javascript:;" class="cancel">Cancel</a>' +
					'</span>' +
				'</div>' +
			'</div>'
		).insertAfter($this);
		$edit.find('select').html(window.routes.map(function(r, i){
			return '<option value="' + r.id + '"' + (r.active == 1 ? '' : ' disabled') + (route == r.id ? ' selected' : '') + '>' + (i + 1) + '</option>';
		}).join(''));

		$edit.find('a').click(function(){
			var $t = $(this);
			var $s = $edit.find('select');
			var newroute = $s.val();
			if ($t.is('.cancel') || newroute == route) {
				$edit.hide();
				$this.show();
				return false;
			}
			if (confirm("Are you sure?")) {
				$.post("/admin/order_hunts/updateRoute/" + id, {
					route: newroute
				}, function(data){
					if (typeof data == 'object' && data.success === true) {
						toastr.success(null, "Route updated; please refresh");
						$edit.hide();
						$this.data('route', newroute).html($s.find('option:selected').text() + ' <small><i class="fa fa-edit"></i></small>').show();
						route = newroute;
					} else {
						toastr.error(null, 'Unknown error occurred; please try again later');
					}
				}, 'json');
			}
			return false;
		});

		return false;
	});

	$('.ibox-content,.index-table').on('click', 'a.refreshdata[data-url][data-elid]', function(){
		var $this = $(this);
		var elid = '#' + $this.data('elid');
		$.get($this.data('url'), function(html){
			var $html = $(html);
			$(elid).html($html.is(elid) ? $html.html() : $html.find(elid).html());
		});
	});

	$('#players-list,#wrong-list').DataTable({
		dom: 'T<"clear">lfrtip',
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv"
				},
				{
					"sExtends": "xls"
				},
				{
					"sExtends": "pdf"
				},
				{
					"sExtends": "print"
				}
			]
		}
	});

});