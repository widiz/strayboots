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
	
	$('#orders-list')/*.on('click', 'a.sendmail', function(){
		var order_id = $(this).data('id'),
			orderinfo = $(this).data('orderinfo');

		bootbox.dialog({
				title: "Send mail",
				message: (orderinfo ?
				'<div class="row">  ' +
					'<div class="col-md-12" style="line-height:1.9"> ' +
						'<div><b>Client:</b> <a target="_blank" href="/admin/clients/edit/' + orderinfo.client_id + '">' + txtRender.display(orderinfo.first_name) + '</a></div>' +
						'<div><b>Order:</b> <a target="_blank" href="/admin/orders/edit/' + order_id + '">#' + order_id + ' ' + txtRender.display(orderinfo.name) + '</a></div>' +
						'<div><b>Order hunts:</b> <a href="/admin/order_hunts/' + order_id + '">' + numberRender.display(orderinfo.hunts) + '</a></div>' +
						(orderinfo.hunts == 0 ? '<span style="font-size:150%">This order has no hunts! no PDF will be sent.</span><br>' : '') + 
						'<br>' +
					'</div>' +
				'</div>' : '') +
				'<div class="row">  ' +
					'<div class="col-md-12"> ' +
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
								$.post("/admin/orders/mail/" + order_id, {
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
			}
		);

		$('#sendto-0').change(function(){
			$('#sendmail-email').prop('disabled', !$(this).is(':checked'));
		});
		$('#sendto-1').change(function(){
			$('#sendmail-email').prop('disabled', $(this).is(':checked'));
		});

		return false;
	})*/.DataTable({
		ajax: "/admin/orders/datatable?cb=" + Math.floor(Math.random() * 1e6) + (window.clientId > 0 ? '&client=' + window.clientId : '') + (window.huntId > 0 ? '&hunt=' + window.huntId : ''),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'id',
				width: 100,
				render: txtRender
			},
			{
				data: 'name',
				render: txtRender
			},
			{
				data: 'first_name',
				render: function(data, type, row, meta){
					return '<a href="/admin/clients/edit/' + row.client_id + '">' + txtRender.display(row.first_name + ' ' + row.last_name) + '</a>';
				}
			},
			{
				data: 'company',
				render: function(data, type, row, meta){
					return '<a href="/admin/clients/edit/' + row.client_id + '">' + txtRender.display(row.company) + '</a>';
				}
			},
			{
				searchable: false,
				data: 'created',
				width: 200,
				render: amPmRender
			},
			{
				orderable: false,
				searchable: false,
				width: 90,
				render: function(data, type, row, meta){
					return '<a href="/admin/order_hunts/' + row.id + '">' + numberRender.display(row.hunts) + '</a>';
				}
			},
			{
				orderable: false,
				searchable: false,
				width: 280/*320*/,
				render: function(data, type, row, meta){
					return '<a href="/admin/order_hunts/' + row.id + '" class="btn btn-default">Hunts</a> ' +
							'<a href="/admin/orders/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							//'<a href="javascript:;" data-id="' + row.id + '" data-orderinfo="' + JSON.stringify(row).replace(/"/g, '&quot;') + '" class="btn sendmail btn-success">Mail</a> ' +
							'<a href="/admin/orders/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			},
			{
				data: 'last_name',
				visible: false,
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3, 4]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3, 4]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3, 4]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3, 4]
				}
			]
		}
	});
});