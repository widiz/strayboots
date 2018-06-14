$(function(){
	var txtRender = $.fn.dataTable.render.text(),
		numberRender = $.fn.dataTable.render.number(',', '.', 0),
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
	$('#wrong-list').DataTable({
		ajax: "/admin/questions/datatableWrong/" + window.qid + "?cb=" + Math.floor(Math.random() * 1e6),
		dom: 'T<"clear">lfrtip',
		processing: true,
		serverSide: true,
		pageLength: 50,
		order: [[0, 'desc']],
		columns: [
			{
				data: 'id',
				width: 100,
				render: txtRender
			},
			{
				data: 'order_id',
				render: function(id, type, row, meta){
					return '<a href="/admin/order_hunts/' + row.order_id + '">' +
								txtRender.display(row.ordername) +
							'</a> / <a href="/admin/hunts/edit/' + row.hunt_id + '">' +
								txtRender.display(row.huntname) +
							'</a>';
				}
			},
			{
				data: 'answer',
				width: 'auto',
				render: txtRender
			},
			{
				orderable: false,
				searchable: false,
				data: 'created',
				width: 220,
				render: amPmRender
			},
			{
				orderable: false,
				searchable: false,
				width: 200,
				render: function(data, type, row, meta){
					return '<a href="javascript:;" data-id="' + row.id + '" class="btn btn-danger addanswer">Add Answer</a>';
				}
			},
			{
				data: 'hunt_id',
				visible: false
			},
			{
				data: 'huntname',
				visible: false
			},
			{
				data: 'ordername',
				visible: false
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
	}).on('click', '.addanswer', function(e){
		e.preventDefault();
		if (confirm('Are you sure you want to add this answer to the question?')) {
			var $this = $(this);
			$.post("/admin/questions/addAnswer", {
				id: $this.data('id')
			}, function(){
				$this.fadeOut(250);
			}, 'json');
		}
	});
});