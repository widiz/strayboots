$(function(){
	var txtRender = $.fn.dataTable.render.text();
	$('#eventemails-list').DataTable({
		ajax: "/admin/event_emails/datatable?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'email_id',
				render: function(data){
					return [
						'Danielle Post Event Email Players',
						'Danielle Post Event Email Client',
						'Nikki Post Event Email',
						'Shauna Post Event Email',
						'Post Event Email Client',
						'Post Event Email Players',
						'B2C Player Post Hunt Email'
					][data] || data;
				}
			},
			{
				data: 'title',
				render: txtRender
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/admin/event_emails/edit/' + row.email_id + '" class="btn btn-warning">Edit</a>';
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