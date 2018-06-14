$(function(){
	var txtRender = $.fn.dataTable.render.text(),
		numberFormat = $.fn.dataTable.render.number(',', '.', 0),
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
				return mon+'/'+day+'/'+d.getFullYear()/*+' '+hours+':'+min+' '+ampm+' EST'*/;
			}
		};

	function getAjaxURL() {
		return '/admin/hunts/datatable/' + $('#hunttype').val() + '?cb=' + Math.floor(Math.random() * 1e6);
	}

	var dt = $('#hunts-list').DataTable({
		ajax: getAjaxURL(),
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
			/*{
				data: 'slug',
				width: 'auto',
				render: txtRender
			},
			{
				searchable: false,
				data: 'type_id',
				width: 'auto',
				render: function(t){
					return typeof window.huntTypes[t] == 'string' ? txtRender.display(window.huntTypes[t]) : '';
				}
			},*/
			{
				data: 'city_id',
				searchable: false,
				width: 'auto',
				render: function(t){
					if (typeof window.cities[t] == 'object')
						return txtRender.display(window.cities[t][0] +
							(typeof window.countries[window.cities[t][1]] == 'string' ? ' / ' + window.countries[window.cities[t][1]] : ''));
					return '';
				}
			},
			/*{
				data: 'time',
				width: 'auto',
				render: txtRender
			},*/
			{
				searchable: false,
				data: 'approved',
				width: 'auto',
				render: function(a){
					return a == 1 ? 'Yes' : 'No';
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'orders',
				width: 'auto',
				render: function(t, type, row){
					return '<a href="/admin/orders?hunt=' + row.id + '">' + numberFormat.display(t) + '<a>';
				}
			},
			{
				orderable: false,
				searchable: false,
				data: 'questions',
				width: 'auto',
				render: numberFormat
			},
			{
				orderable: false,
				searchable: false,
				data: 'routes',
				width: 'auto',
				render: numberFormat
			},
			{
				//orderable: false,
				searchable: false,
				data: 'last_play',
				width: 'auto',
				render: amPmRender
			},
			{
				searchable: false,
				data: 'last_edit',
				width: 'auto',
				render: amPmRender
			},
			{
				orderable: false,
				searchable: false,
				width: 310,
				render: function(data, type, row, meta){
					return '<a href="/admin/hunts/duplicate/' + row.id + '" class="btn btn-info" onclick="return confirm(\'Are you sure?\')">Duplicate</a> ' +
							'<a href="/admin/map/preview?hunt=' + row.id + '" class="btn btn-primary map-preview" target="_blank">Map</a> ' +
							'<a href="/admin/hunts/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
							'<a href="/admin/hunts/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
				}
			}
		],
		tableTools: {
			sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
			aButtons: [
				{
					"sExtends": "csv",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7, 8]
				},
				{
					"sExtends": "xls",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7, 8]
				},
				{
					"sExtends": "pdf",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7, 8]
				},
				{
					"sExtends": "print",
					"mColumns": [0, 1, 2, 3, 4, 5, 6, 7, 8]
				}
			]
		}
	});
	$('#hunts-list').on('click', '.map-preview', function(){
		var href = $(this).attr('href');
		window.open(href, 'mappreview', 'fullscreen=no,height=550,width=1000,menubar=no,scrollbars=no,status=no,titlebar=no,toobar=no');
		return false;
	});
	$('#hunttype').change(function(){
		dt.ajax.url(getAjaxURL());
		dt.ajax.reload();
	});
});