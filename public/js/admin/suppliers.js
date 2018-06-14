$(function(){
	var txtFormat = $.fn.dataTable.render.text(),
		numberFormat = $.fn.dataTable.render.number(',', '.', 0),
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

	$('#suppliers-list').DataTable({
		ajax: "/admin/suppliers/datatable?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'id',
				width: 100,
				render: txtFormat
			},
			{
				data: 'email',
				width: 'auto',
				render: txtFormat
			},
			{
				data: 'company',
				width: 'auto',
				render: txtFormat
			},
			{
				/*orderable: false,*/
				data: 'first_name',
				width: 'auto',
				render: function(first, m, row) {
					return txtFormat.display(first + ' ' + row.last_name).trim();
				}
			},
			{
				data: 'phone',
				width: 'auto',
				render: txtFormat
			},
			{
				searchable: false,
				data: 'created',
				width: 'auto',
				render: amPmRender
			},
			{
				searchable: false,
				data: 'active',
				width: 100,
				render: function(s){
					return s == 1 ? 'Yes' : 'No';
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'products',
				width: 100,
				render: function(products, type, row, meta){
					return '<a href="/suppliers/login/admin/' + row.id + '" target="_blank">' + numberFormat.display(products) + '</a>';
				}
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/suppliers/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="javascript:;" data-id="' + row.id + '" class="btn btn-success sendmail">Send Pwd</a> ' +
							'<a href="/admin/suppliers/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			},
			{
				data: 'last_name',
				visible: false
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3, 4, 5, 6]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3, 4, 5, 6]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3, 4, 5, 6]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3, 4, 5, 6]
				}
			]
		}
	}).on('click', '.btn.sendmail', function(){
		if (confirm("Are you sure?")) {
			$.post("/admin/suppliers/sendpass/" + $(this).data('id'), { }, function(data){
				if (typeof data == 'object' && typeof data.success == 'object' && data.success.http_response_code == 200)
					toastr.success(null, "Email sent");
				else
					toastr.error(null, 'Unknown error occurred; please try again later');
			}, 'json');
		} else {
			return false;
		}
	});
});