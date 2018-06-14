$(function(){
	$('#pointtypes-list').DataTable({
		ajax: "/admin/point_types/datatable?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'id',
				width: 100,
				render: $.fn.dataTable.render.text()
			},
			{
				data: 'name',
				width: 'auto',
				render: $.fn.dataTable.render.text()
			},
			{
				orderable: false,
				searchable: false,
				data: 'points',
				width: 150,
				render: $.fn.dataTable.render.number(',', '.', 0)
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/point_types/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/point_types/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2]
				}
			]
		}
	});
});