$(function(){
	var status = [
		"Text",
		"Photo",
		"Completion",
		"Other",
		"Timer",
		"Choose"
	];
	var txtRender = $.fn.dataTable.render.text(),
		numberFormat = $.fn.dataTable.render.number(',', '.', 0);
	$('#questiontypes-list').DataTable({
		ajax: "/admin/question_types/datatable?cb=" + Math.floor(Math.random() * 1e6),
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
				width: 'auto',
				render: txtRender
			},
			{
				searchable: false,
				data: 'type',
				width: 250,
				render: function(s){
					return typeof status[s] == 'string' ? status[s] : '';
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'score',
				width: 150,
				render: numberFormat
			},
			{
				searchable: false,
				data: 'custom',
				width: 150,
				render: function(s){
					return s == 1 ? 'Yes' : 'No';
				}
			},
			{
				searchable: false,
				data: 'limitAnswers',
				width: 150,
				render: function(s){
					return s == 1 ? 'Yes' : 'No';
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'questions',
				width: 150,
				render: numberFormat
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/question_types/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/question_types/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
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
	});
});