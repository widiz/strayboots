$(function(){
	$('#blocked-list').DataTable({
		ajax: "/admin/blocked/datatable?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'email',
				width: 'auto',
				render: $.fn.dataTable.render.text()
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/blocked/edit/' + row.email + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/blocked/delete/' + row.email + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0]
				},
				{
					"sExtends": "xls",
					"mColumns": [0]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0]
				},
				{
					"sExtends": "print",
					"mColumns": [0]
				}
			]
		}
	});
});