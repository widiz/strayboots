$(function(){
	var numRender = $.fn.dataTable.render.number(',', '.', 0),
		txtRender = $.fn.dataTable.render.text();
	$('#loginpages-list').DataTable({
		ajax: "/admin/login_pages/datatable?cb=" + Math.floor(Math.random() * 1e6),
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
				data: 'slug',
				width: 'auto',
				render: function(data, type, row){
					return '<a href="/' + data.replace(/"/g, '&quote;') + '" target="_blank">' + txtRender.display(data) + '</a>';
				}
			},
			{
				data: 'title',
				width: 'auto',
				render: txtRender
			},
			{
				data: 'sub_title',
				width: 'auto',
				render: txtRender
			},
			{
				data: 'order_hunt_id',
				render: function(data, type, row){
					return '<a href="/admin/order_hunts/edit/' + data + '">#' + numRender.display(data) + ' ' + txtRender.display(row.order_name + ' / ' + row.hunt_name) + '</a>';
				}
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/login_pages/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/login_pages/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			},
			{
				orderable: false,
				searchable: true,
				visible: false,
				data: 'order_name'
			},
			{
				orderable: false,
				searchable: true,
				visible: false,
				data: 'hunt_name'
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