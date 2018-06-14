$(function(){
	var numRender = $.fn.dataTable.render.number(',', '.', 0);
	$('#tags-list').DataTable({
		ajax: "/admin/tags/datatable?cb=" + Math.floor(Math.random() * 1e6),
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
				data: 'tag',
				width: 'auto',
				render: $.fn.dataTable.render.text()
			},
			{
				orderable: false,
				searchable: false,
				data: 'questions',
				width: 150,
				render: function(data, type, row){
					return '<a href="/admin/questions/?tag=' + row.id + '">' + numRender.display(data) + '</a>';
				}
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/tags/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/tags/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
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