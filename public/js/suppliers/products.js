$(function(){
	var txtRender = $.fn.dataTable.render.text(),
		amPmRender = {
			display: function(d) {
				if (!d)
					return '';
				if (typeof d != 'object') {
					d = d.split(/[^\d]/);
					d = new Date(d[0], d[1] - 1, d[2], d[3], d[4], d[5]);
				}
				var hours=d.getHours(),min=d.getMinutes(),
					mon=d.getMonth()+1,day=d.getDate(),ampm='AM';
				if(hours>11){if(hours>12)hours-=12;ampm='PM';}
				if(hours<10)hours='0'+hours;if(min<10)min='0'+min;
				if(day<10)day='0'+day;if(mon<10)mon='0'+mon;
				return mon+'/'+day+'/'+d.getFullYear()+' '+hours+':'+min+' '+ampm+' EST';
			}
		};

	$('#products-list').DataTable({
		ajax: "/suppliers/products/datatable?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[1, 'desc']],
		columns: [
			/*{
				data: 'id',
				width: 100,
				render: txtRender
			},*/
			{
				data: 'name',
				render: txtRender
			},
			{
				data: 'address',
				render: txtRender
			},
			{
				orderable: false,
				searchable: false,
				width: 90,
				data: 'price',
				render: $.fn.dataTable.render.number(',', '.', 2)
			},
			{
				searchable: false,
				data: 'created',
				width: 200,
				render: amPmRender
			},
			{
				orderable: false,
				searchable: false,
				width: 300,
				render: function(data, type, row, meta){
					return '<a href="/suppliers/products/edit/' + row.id + '" class="btn btn-warning">Edit</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3/*, 4*/]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3/*, 4*/]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3/*, 4*/]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3/*, 4*/]
				}
			]
		}
	});
});