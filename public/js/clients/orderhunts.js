$(function(){
	var numberRender = $.fn.dataTable.render.number(',', '.', 0),
		txtRender = $.fn.dataTable.render.text()
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

	$('#orderhunts-list').DataTable({
		ajax: "/clients/order_hunts/datatable/" + window.orderId + "?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		order: [[0, 'desc']],
		columns: [
			/*{
				data: 'id',
				width: 120,
				render: txtRender
			},*/
			{
				data: 'hunt_id',
				width: 'auto',
				render: function(data, type, row) {
					return txtRender.display(row.name);
				}
			},
			{
				data: 'max_players',
				width: 120,
				render: numberRender
			},
			{
				data: 'max_teams',
				width: 120,
				render: numberRender
			},
			{
				searchable: false,
				data: 'start',
				//width: 200,
				render: amPmRender
			},
			{
				searchable: false,
				data: 'finish',
				//width: 200,
				render: amPmRender
			},
			{
				searchable: false,
				data: 'expire',
				//width: 200,
				render: amPmRender
			},
			{
				orderable: false,
				searchable: false,
				width: 130/*400*/,
				render: function(data, type, row, meta){
					return '<a href="/clients/order_hunts/summary/' + row.id + '" class="btn btn-warning">View Hunt Details</a>';
					/*'<a href="/clients/teams/' + row.id + '" class="btn btn-danger">Teams</a>&nbsp;' +
							'<a href="/clients/order_hunts/customize/' + row.id + '" class="btn btn-primary">Customize</a>&nbsp;' +
							//'<a href="/clients/bonus/' + row.id + '" class="btn btn-warning">BonusQ</a>&nbsp;' +
							'<a href="/clients/order_hunts/downloadPDF/' + row.id + '" class="btn btn-success">PDF</a>&nbsp;' +
							'<a href="/clients/order_hunts/summary/' + row.id + '" class="btn btn-info">Summary</a>';*/
				}
			},
			{
				data: 'name',
				visible: false
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3, 4, 5/* ,6*/]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3, 4, 5/* ,6*/]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3, 4, 5/* ,6*/]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3, 4, 5/* ,6*/]
				}
			]
		},
        createdRow: function (row, data, index){
			if ((data.flags & 4) == 4)
				$(row).addClass('canceled');
        }
	});
});