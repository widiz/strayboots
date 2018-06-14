$(function(){

	var renderText = $.fn.dataTable.render.text().display;
	
	$(".select2-auto").select2({
		placeholder: "Please choose one",
		allowClear: true
	});
	$('.clockpicker').clockpicker();

	// activate Nestable for list 1
	var nestableList = $('#nestable').on('mousedown', '.dd-handle a', function(e){
		e.preventDefault();
		if ($(this).hasClass('remove')) {
			if (confirm("Are you sure?")) {
				$(e.target).closest('.dd-item:not(.del)').addClass('del').slideUp(250, function(){
					$(this).remove();
				});
				setPQ();
			}
			return false;
		}
		throw '';
	})/*.on('mousedown', 'i', function(e){
		e.preventDefault();
		var $target = $(e.target).closest('.dd-item:not(.del):not(:first-child)');
		if ($target.length) {
			var info = $target.data('info');
			info.s = !info.s;
			$target.find('i').toggleClass('fa-flag', info.s).toggleClass('fa-flag-o', !info.s);
			$target.data('info', info);
			setPQ();
		}
		return false;
	})*/.nestable({
		maxDepth: 1
	}).on('change', setPQ).find('.dd-list');

	var cityField = $('#fieldCityId'),
		addPointField = $('#addPoint'),
		addQuestionField = $('#addQuestion'),
		addIsStartField = $('#addIsStart'),
		filterTags = $('#filterTags'),
		pqField = $('#pqField'),
		bpFieldVal = $('#fieldBreakpointsVal'),
		bpField = $('#fieldBreakpoints'),
		pointsCoordinates = {};

	cityField.change(function(){
		nestableList.find('li').remove();
		addQuestionField.prop('disabled', true).find('option').remove();
		addQuestionField.select2({
			placeholder: "Please choose one",
			allowClear: true,
			data: [
				{
					id: 0,
					text: 'Select a point first'
				}
			]
		});
		var cid = cityField.val();
		if (cid > 0) {
			$.ajax({
				url: '/admin/points/getPointsByCity/' + cid,
				success: function(data){
					if (typeof data == 'object' && data.success === true) {
						pointsCoordinates = data.coordinates;
						addPointField.prop('disabled', false).find('option').remove();
						data.results.unshift(data.results[0]);
						data.results[1] = {
							id: 0,
							text: ' - Generic - '
						};
						addPointField.select2({
							placeholder: "Please choose one",
							allowClear: true,
							data: data.results
						});
					} else {
						return this.error(data);
					}
				},
				error: function(){
					alert('An error occurred; please refresh and try again');
				}
			});
		}
	}).trigger('change');

	addPointField.change(function(){
		var id = addPointField.val();
		if (id === null)
			return;

		$.ajax({
			url: '/admin/questions/getQuestionsByPoint/' + id + '?tags=' + (filterTags.val() || []).join(','),
			success: function(data){
				if (typeof data == 'object' && data.success === true) {
					addQuestionField.prop('disabled', false).find('option').remove();
					addQuestionField.select2({
						placeholder: "Please choose one",
						allowClear: true,
						data: data.results
					});
				} else {
					return this.error(data);
				}
			},
			error: function(){
				alert('An error occurred; please refresh and try again');
			}
		});
	});

	filterTags.change(function(){
		addPointField.trigger('change');
	}).select2({
		placeholder: "Filter Tags",
		allowClear: true
	});

	$('#addPQ').click(function(){
		var pid = parseInt(addPointField.val()),
			qid = parseInt(addQuestionField.val());
		if ((pid > 0 || pid === 0) && qid > 0) {
			if (pid === 0 && nestableList.find('li[data-qid="' + qid + '"]').length) {
				toastr.error(null, 'This question is already in use');
			} else if (pid > 0 && nestableList.find('li[data-pid="' + pid + '"]').length) {
				toastr.error(null, 'This point is already in use');
			} else {
				var info = {
					p: [pid, addPointField.select2('data')[0].text],
					q: [qid, addQuestionField.select2('data')[0].text],
					s: addIsStartField.prop('checked')
				};
				addPQ(info);
				setPQ();
				addPointField.select2('val', '');
				//addIsStartField.prop('checked', false);
				addQuestionField.prop('disabled', true).find('option').remove();
				addQuestionField.select2('val', '');
				addQuestionField.select2({
					placeholder: "Please choose one",
					allowClear: true,
					data: [
						{
							id: 0,
							text: 'Select a point first'
						}
					]
				});
			}
		} else {
			toastr.error(null, 'Please choose a point and a question');
		}
		return false;
	});

	$('.map-preview').click(function(){
		var points = $(this).closest('fieldset').find('.dd-item[data-pid]'),
			data = [];
		var numPoints = points.length - 1,
			charCodeAdd = 48;
		for (var p = 0; p <= numPoints; p++) {
			var point = points.eq(p);
			var pid = point.data('pid');
			var info = point.data('info'),
				coordinates = pointsCoordinates[pid];
			if (++charCodeAdd == 91)
				charCodeAdd += 6;
			if (charCodeAdd == 58)
				charCodeAdd += 7;
			if (pid > 0 && typeof coordinates == 'object' && coordinates[0] != 0 && coordinates[1] != 0) {
				data.push({
					latitude: coordinates[0],
					longitude: coordinates[1],
					label: p + 1, //p == 0 ? 'S' : (p == numPoints ? 'F' : p/*String.fromCharCode(charCodeAdd)*/),
					info: (p == 0 ? "<h2>First Point</h2>" : (p == numPoints ? "<h2>Last Point</h2>" : ("<h2>Point " + (p + 1) + "</h2>"))) + info.p[1] + '<br>' + info.q[1]
				});
			}
		}
		var form = document.createElement('form');
		form.setAttribute('method', 'post');
		form.setAttribute('action', '/admin/map/preview');
		form.setAttribute('target', 'mappreview');
		var hiddenField = document.createElement('input'); 
		hiddenField.setAttribute('type', 'hidden');
		hiddenField.setAttribute('name', 'data');
		hiddenField.setAttribute('value', JSON.stringify(data));
		form.appendChild(hiddenField);
		document.body.appendChild(form);
		window.open('', 'mappreview', 'fullscreen=no,height=550,width=1000,menubar=no,scrollbars=no,status=no,titlebar=no,toobar=no');
		form.submit();
		return false;
	});

	bpField.change(function(){
		bpFieldVal.val(($(this).val() || []).join(','));
	});

	function addPQ(info){
		$(
			'<li class="dd-item" data-pid="' + info.p[0] + '" data-qid="' + info.q[0] + '">' +
				'<div class="dd-handle">' +
					'<span class="num">' + (nestableList.find('li').length + 1) + '</span>: ' +
					renderText(info.p[1]) +
					'<span>&nbsp; / &nbsp;' + 
						renderText(info.q[1]) +
					'</span>' + 
					'<a href="/admin/questions/edit/' + info.q[0] + '" target="_blank" class="qlink uk-nestable-nodrag">Q</a>' +
					'<i class="uk-nestable-nodrag fa fa-flag' + /*(info.s ? '' : '-o') +*/ '"></i>' +
					'<a href="javascript:;" class="remove uk-nestable-nodrag">x</a>' +
				'</div>' +
			'</li>'
		).data('info', info).appendTo(nestableList);
	}
	function setPQ(){
		var list = nestableList.find('.dd-item[data-pid]:not(.del)'),
			breakpoints = '';
		for (var i = 1; i < list.length; i++)
			breakpoints += '<option>' + i + '</option>';
		bpField.html(breakpoints).val((bpFieldVal.val() || '').split(',')).trigger('change.select2');
		pqField.val(JSON.stringify(list.map(function(i){
			var $this = $(this);
			$this.find('.num').text(i + 1);
			return $this.data('info');
		}).toArray()));
	}

	var pqVal = pqField.val();
	if (pqVal.length) {
		try {
			pqVal = JSON.parse(pqVal);
			if (typeof pqVal == 'object') {
				for (var i = 0; i < pqVal.length; i++)
					addPQ(pqVal[i]);
			}
		} catch(e) {}
	}
	setPQ();

});