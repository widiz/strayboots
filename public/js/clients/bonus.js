$(function(){
	var numberRender = $.fn.dataTable.render.number(',', '.', 0),
		questionTypes = ["Team", "Private"];
	$('#bonus-list').DataTable({
		ajax: '/clients/bonus/datatable/' + window.orderHuntId + '?cb=' + Math.floor(Math.random() * 1e6),
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
				data: 'type',
				width: 'auto',
				render: function(t){
					return typeof questionTypes[t] == 'string' ? questionTypes[t] : '';
				}
			},
			{
				data: 'question',
				width: 'auto',
				render: $.fn.dataTable.render.text()
			},
			{
				data: 'score',
				width: 'auto',
				render: function(score){
					return score === null ? '' : numberRender.display(score);
				}
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/clients/bonus/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							(window.removable ? '<a href="/clients/bonus/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>' : '');
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