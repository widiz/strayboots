$(function(){
	$('#custom-events-list').DataTable({
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