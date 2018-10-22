$(function(){
	var numberRender = $.fn.dataTable.render.number(',', '.', 0),
		txtRender = $.fn.dataTable.render.text(),
		amPmRender = {
			display: function(d) {
				if (!d)
					return '';
				if (typeof d != 'object') {
					d = d.split(/[^\d]/);
					d = new Date(d[0], d[1] - 1, d[2], d[3], d[4], d[5]);
				}
				var hours=d.getHours(),min=d.getMinutes(),
					mon=d.getMonth()+1,day=d.getDate(),ampm='AM';
				if(hours>11){if(hours>12)hours-=12;ampm='PM';}
				if(hours<10)hours='0'+hours;if(min<10)min='0'+min;
				if(day<10)day='0'+day;if(mon<10)mon='0'+mon;
				return mon+'/'+day+'/'+d.getFullYear()+' '+hours+':'+min+' '+ampm+' EST';
			}
		};

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

	$('#orderhunts-list').DataTable({
		ajax: "/admin/order_hunts/datatable/" + window.orderId + "?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'id',
				width: 120,
				render: txtRender
			},
			{
				data: 'hunt_id',
				width: 'auto',
				render: function(data, type, row, meta){
					return '<a href="/admin/hunts/edit/' + row.hunt_id + '">' + txtRender.display(row.name) + '</a>';
				}
			},
			{
				data: 'city_id',
				searchable: false,
				width: 'auto',
				render: function(t){
					if (typeof window.cities[t] == 'object')
						return txtRender.display(window.cities[t][0] +
							(typeof window.countries[window.cities[t][1]] == 'string' ? ' / ' + window.countries[window.cities[t][1]] : ''));
					return '';
				}
			},
			{
				data: 'max_players',
				width: 60,
				render: numberRender
			},
			{
				data: 'max_teams',
				width: 60,
				render: numberRender
			},
			{
				searchable: false,
				data: 'start',
				//width: 200,
				render: amPmRender
			},
			{
				searchable: false,
				data: 'finish',
				//width: 200,
				render: amPmRender
			},
			{
				searchable: false,
				data: 'expire',
				//width: 200,
				render: amPmRender
			},
			{
				orderable: false,
				searchable: false,
				width: 550,
				render: function(data, type, row, meta){
					return '<a href="/admin/order_hunts/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/order_hunts/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a> ' +
							'<a href="/clients/bonus/' + row.id + '" target="_blank" class="btn btn-primary">BonusQ</a> ' +
							'<a href="/admin/custom_events/index/' + row.id + '" class="btn btn-info btn-outline">Events</a> ' +
							'<a href="javascript:;" data-title="' + quoteattr(row.name) + '" data-start="' + amPmRender.display(row.start) + '" data-id="' + row.id + '" class="btn btn-success sendmail">Pre-Event Email</a> ' +
							//'<a href="javascript:;" data-title="' + row.name.replace(/"/g, "&quote;") + '" data-id="' + row.id + '" class="btn btn-success sendpe">PE mail</a> ' +
							'<a href="/admin/order_hunts/summary/' + row.id + '" class="btn btn-info">Sum</a> ' +
							'<a href="javascript:;" data-id="' + row.id + '" class="btn btn-default teams">Teams</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3, 4, 5 ,6 ,7]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3, 4, 5 ,6 ,7]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3, 4, 5 ,6 ,7]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3, 4, 5 ,6 ,7]
				}
			]
		}
	}).on('click', '.btn.sendmail', function(){
		var $this = $(this);
		var id = $this.data('id'),
			title = $this.data('title') || '',
			start = $this.data('start') || '';

		bootbox.dialog({
			title: "Send Pre-Event Email",
			message: '<div class="row">  ' +
				'<div class="col-md-12"> ' +
					'<div>Client: ' + window.clientName + '</div>' +
					'<div>Order: ' + window.orderName + '</div>' +
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
				download: {
					label: "Download",
					className: "btn-danger",
					callback: function(){
						window.open(document.location.protocol + '//' + document.location.host + '/admin/order_hunts/downloadPDF/' + id);
						return false;
					}
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
							$.post("/admin/order_hunts/mail/" + id, {
								email: testing ? $email.val() : ''
							}, function(data){
								if (typeof data == 'object' && typeof data.success == 'object' && data.success.http_response_code == 200)
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
	});

	/*$('#orderhunts-list').on('click', '.btn.sendpe', function(){
		var $this = $(this);
		var id = $this.data('id'),
			title = $this.data('title') || '';
		bootbox.dialog({
			title: "Send post event mail - " + window.orderName + ' / ' + title,
			message: '<div class="row">  ' +
				'<div class="col-md-12"> ' +
					'<form class="form-horizontal"> ' +
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
	});*/

	$('#orderhunts-list').on('click', '.btn.teams', function(){
		var id = $(this).data('id'),
			loading = $(document.createElement('div')).attr('id', 'loading-indicator').appendTo('#page-wrapper').fadeIn(100);
		$.getJSON("/admin/order_hunts/getTeams/" + id, function(response){
			if (typeof response == 'object' && response.success === true) {
				bootbox.dialog({
					title: "Teams - OrderHunt #" + id,
					message: '<div class="row">  ' +
						'<div class="col-md-12"> ' +
							'<table class="table table-striped table-hover">' +
								'<thead>' +
									'<tr>' +
										'<td>#</td>' +
										'<td>Route</td>' +
										'<td>Team</td>' +
										'<td>Leader</td>' +
										'<td>Player</td>' +
									'</tr>' +
								'</thead>' +
								'<tbody id="oh-teams"></tbody>' +
							'</table>' +
						'</div>' +
					'</div>',
					buttons: {
						default: {
							label: "Close",
							className: "btn-default"
						},
						success: {
							label: "Send PDF to leaders",
							className: "btn-success",
							callback: function(){
								if (confirm("Are you sure?")) {
									$.post("/admin/order_hunts/mailTeams/" + id, { }, function(data){
										if (typeof data == 'object' && data && data.success === true)
											toastr.success(null, (data.sent || 'x') + " emails sent");
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
				$('#oh-teams').append($.map(response.teams, function(team){
					return '<tr>' +
								'<td>' + team.id + '</td>' +
								'<td>' + team.route + '</td>' +
								'<td>' + team.name + '</td>' +
								'<td>' + team.activation_leader + '</td>' +
								'<td>' + team.activation_player + '</td>' +
							'</tr>';
				}));
			}
		}).always(function(){
			loading.stop().fadeOut(150, function(){
				loading.remove();
			});
		});

		return false;
	});

});