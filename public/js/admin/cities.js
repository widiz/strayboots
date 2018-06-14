$(function(){
	var status = [
		"Active",
		"New",
		"Coming Soon",
		"Contact Only",
		"B2c"
	];
	$('#cities-list').DataTable({
		ajax: "/admin/cities/datatable/" + window.countryId + "?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'id',
				width: 120,
				render: $.fn.dataTable.render.text()
			},
			{
				data: 'name',
				width: 'auto',
				render: $.fn.dataTable.render.text()
			},
			{
				searchable: false,
				data: 'status',
				width: 300,
				render: function(s){
					return typeof status[s] == 'string' ? status[s] : '';
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'points',
				width: 220,
				render: $.fn.dataTable.render.number(',', '.', 0)
			},
			{
				orderable: false,
				searchable: false,
				width: 220,
				render: function(data, type, row, meta){
					return '<a href="/admin/cities/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/cities/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3]
				}
			]
		}
	});
});