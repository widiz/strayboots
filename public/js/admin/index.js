$(function(){
	$.ajaxSetup({
		cache: false
	});

	var txtRender = $.fn.dataTable.render.text(),
		numberFormat = $.fn.dataTable.render.number(',', '.', 0),
		lists = ['active', 'last', 'coming'],
		amPmRender = {
			display: function(d, noest) {
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
				return mon+'/'+day+'/'+d.getFullYear()+' '+hours+':'+min+' '+ampm+(noest === 'noest' ? '' : ' EST');
			}
		};

	for (var i = 0; i < lists.length; i++) {
		var now = new Date(window.serverDate),m,d;
		m = now.getMonth() + 1; d = now.getDate();
		var today = now.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d);
		now.setDate(now.getDate() + 1);
		m = now.getMonth() + 1; d = now.getDate();
		var tomorrow = now.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d);
		(function(list){
			var dt;
			dt = $('#' + list + '-hunts').on('click', 'a.opendetails', function(){
				var $this = $(this);
				var id = $this.data('id');
				var tr = $this.closest('tr');
				var row = dt.row(tr);
				if (row.child.isShown()) {
					tr.removeClass('details');
					row.child.hide();
				} else {
					tr.addClass('details');
					var r = row.child();
					if (typeof r == 'object' && r.length) {
						row.child.show();
					} else {
						row.child('<div id="oh-' + id + '" class="app_loading orderhunt-box-i"></div>').show();
						$.get("/admin/order_hunts/summary/" + id, function(html){
							var $content = $('#oh-' + id).html(html).removeClass('app_loading');
							$content.find('.blueimp-gallery').remove();
							//blueimp.Gallery($content.find('.lightBoxGallery a'));
							setTimeout(function(){
								if (typeof window.processLeaderMaps == 'function')
									window.processLeaderMaps();
							}, 50);
						});
					}
				}
			}).on('click', '.design-preview', function(){
				var href = $(this).attr('href');
				window.open(href, 'designpreview', 'fullscreen=no,height=600,width=365,menubar=no,scrollbars=no,status=no,titlebar=no,toobar=no');
				return false;
			}).DataTable({
				ajax: "/admin/index/datatable/" + list + "?cb=" + Math.floor(Math.random() * 1e6),
				dom: 'T<"clear">lfrtip',
				processing: true,
				serverSide: true,
				order: [list == 'coming' ? [6, 'asc'] : [0, 'desc']],
				columns: [
					{
						data: 'id',
						width: 100,
						render: function(id, meta, row){
							return '<a href="/admin/order_hunts/edit/' + id + '">' + txtRender.display(id) + "</a>";
						}
					},
					{
						data: 'order_id',
						width: 'auto',
						render: function(id, meta, row){
							return '<a href="/admin/order_hunts/' + id + '">' + txtRender.display(row.name) + "</a>";
						}
					},
					{
						data: 'hunt_id',
						width: 'auto',
						render: function(id, meta, row){
							return '<a href="/admin/hunts/edit/' + id + '">' + txtRender.display(row.huntname) + "</a>";
						}
					},
					{
						data: 'cityname',
						width: 'auto'
					},
					{
						data: 'client_id',
						width: 'auto',
						render: function(id, meta, row){
							return '<a href="/admin/clients/edit/' + id + '">' + txtRender.display(row.company) + "</a>";
						}
					},
					{
						orderable: false,
						searchable: false,
						width: 50,
						render: function(data, type, row, meta){
							return numberFormat.display(row.max_teams) + ' / ' + numberFormat.display(row.max_players);
						}
					},
					{
						searchable: false,
						data: 'start',
						width: 'auto',
						render: amPmRender
					},
					{
						searchable: false,
						data: 'finish',
						width: 'auto',
						render: amPmRender
					},
					{
						searchable: false,
						data: 'start_local',
						width: 'auto',
						render: function(data, type, row, meta){
							if (!data) return '';
							return amPmRender.display(data, 'noest') + ' ' + row.Abbreviation;
						}
					},
					{
						searchable: false,
						data: 'finish_local',
						width: 'auto',
						render: function(data, type, row, meta){
							if (!data) return '';
							return amPmRender.display(data, 'noest') + ' ' + row.Abbreviation;
						}
					},
					{
						orderable: false,
						searchable: false,
						width: 50,
						render: function(data, type, row, meta){
							return '<a href="javascript:;" class="btn btn-default opendetails" data-id="' + row.id + '"><i class="fa fa-plus"></i></a>';
						}
					},
					{
						data: 'name',
						visible: false
					},
					{
						data: 'huntname',
						visible: false
					},
					{
						data: 'company',
						visible: false
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
				},
				rowCallback: function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
					if (aData.start.substr(0, 10) == today)
						$(nRow).addClass('today');
					else if (aData.start.substr(0, 10) == tomorrow)
						$(nRow).addClass('tomorrow');
				}
			});
		})(lists[i]);
	}

});