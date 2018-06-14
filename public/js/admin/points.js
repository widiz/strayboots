$(function(){
	var txtRender = $.fn.dataTable.render.text();
	$('#points-list').DataTable({
		ajax: "/admin/points/datatable?cb=" + Math.floor(Math.random() * 1e6),
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
				data: 'internal_name',
				width: 'auto',
				render: txtRender
			},
			{
				data: 'name',
				width: 'auto',
				render: txtRender
			},
			{
				data: 'type_id',
				width: 'auto',
				render: function(t){
					return typeof window.pointTypes[t] == 'string' ? txtRender.display(window.pointTypes[t]) : '';
				}
			},
			{
				data: 'city_id',
				searchable: false,
				width: 'auto',
				render: function(t){
					if (typeof window.cities[t] == 'object')
						return txtRender.display(window.cities[t][0]) +
							(typeof window.countries[window.cities[t][1]] == 'string' ? ' / ' + window.countries[window.cities[t][1]] : '');
					return '';
				}
			},
			/*{
				orderable: false,
				searchable: false,
				data: 'countrycity',
				width: 'auto',
				render: txtRender
			},*/
			{
				orderable: false,
				searchable: false,
				width: 'auto',
				render: function(data, type, row, meta){
					return txtRender.display(row.longitude + ' ' + row.latitude);
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'questions',
				width: 'auto',
				render: $.fn.dataTable.render.number(',', '.', 0)
			},
			{
				orderable: false,
				searchable: false,
				data: 'hunt_points',
				width: 'auto',
				render: $.fn.dataTable.render.number(',', '.', 0)
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/points/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/points/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
				}
			]
		}
	});
});