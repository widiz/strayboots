$(function(){

	var renderText = $.fn.dataTable.render.text().display,
		$routes = $('#routes'),
		$routesField = $('#routesField'),
		generalCounter = 0;

	$routes.on('click', '.collapse-link', function(){
		var $this = $(this);
		var ibox = $this.closest('div.ibox');
		ibox.find('div.ibox-content').slideToggle(200);
		$this.find('i').toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
		ibox.toggleClass('border-bottom');
		setTimeout(function(){
			ibox.resize();
			ibox.find('[id^=map-]').resize();
		}, 50);
		return false;
	});

	$routes.on('change', '.checkbox-inline input', function(){
		var $this = $(this);
		if (!$this.is(':checked')) {
			var ohid = $this.closest('.ibox').data('oh');
			if (ohid > 0) {
				$this.prop('checked', true);
				bootbox.alert('This route is already used in an active <a href="/admin/order_hunts/edit/' + ohid + '" target="_blank">order</a>.');
				return false;
			}
		}
		return true;
	});

	$routes.on('click', '.ibox-tools .close-link', function(){
		var ohid = $(this).closest('.ibox').data('oh');
		if (ohid > 0) {
			bootbox.alert('This route is already used in an active <a href="/admin/order_hunts/edit/' + ohid + '" target="_blank">order</a>.');
			return false;
		} else if (confirm("Are you sure?")) {
			var $box = $(this).closest('div.ibox');
			$box.find('.nestable.dd').nestable('destroy');
			$box.remove();
			$routes.find('h5 > span').each(function(i){
				this.innerText = i + 1;
			});
		}
		return false;
	});

	var huntPointsData = {},
		huntPointsIndex = {},
		pointsCoordinates = window.pointsCoordinates || {},
		defaultRoute = {
			id: 0,
			points: [],
			active: true
		}, i;
	for (i = 0; i < window.huntPoints.length; i++) {
		defaultRoute.points.push({
			id: window.huntPoints[i].i
		});
		huntPointsData[window.huntPoints[i].i] = window.huntPoints[i];
		huntPointsIndex[window.huntPoints[i].i] = i + 1;
	}

	$('#addBtn').click(function(){
		addRoute(defaultRoute, true);
		return false;
	});

	try {
		var currentRoutes = JSON.parse($routesField.val());
		for (i = 0; i < currentRoutes.length; i++)
			addRoute(currentRoutes[i]);
	} catch(e) {}

	$('.form-horizontal').submit(function(){
		setRoutes();
		var valid = true;
		$routes.children().each(function(i){
			var items = $(this).find('.dd-item');
			if (items.length < 2) {
				toastr.error(null, 'Route #' + (i + 1) + ': Less than two points');
				valid = false;
			}
			if (items.first().find('.fa-flag').length !== 1) {
				toastr.error(null, 'Route #' + (i + 1) + ': First point must be a start point');
				valid = false;
			}
		});
		return valid;
	});

	$('.map-preview').click(function(){
		var points = $(this).closest('.ibox').find('.dd-item[data-id]'),
			data = [];
		var numPoints = points.length - 1,
			charCodeAdd = 48;
		for (var p = 0; p <= numPoints; p++) {
			var point = points.eq(p);
			var pid = point.data('id');
			var info = huntPointsData[pid],
				coordinates = pointsCoordinates[info.p[0]];
			if (++charCodeAdd == 91)
				charCodeAdd += 6;
			if (charCodeAdd == 58)
				charCodeAdd += 7;
			if (pid > 0 && typeof coordinates == 'object' && coordinates[0] != 0 && coordinates[1] != 0) {
				data.push({
					latitude: coordinates[0],
					longitude: coordinates[1],
					label: p + 1,//p == 0 ? 'S' : (p == numPoints ? 'F' : p/*String.fromCharCode(charCodeAdd)*/),
					info: (p == 0 ? "<h2>First Point</h2>" : (p == numPoints ? "<h2>Last Point</h2>" : ("<h2>Point " + (p + 1) + "</h2>"))) + info.p[1] + '<br>' + info.q[1]
				});
			}
		}
		var form = document.createElement("form");
		form.setAttribute("method", "post");
		form.setAttribute("action", "/admin/map/preview");
		form.setAttribute("target", "mappreview");
		var hiddenField = document.createElement("input"); 
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "data");
		hiddenField.setAttribute("value", JSON.stringify(data));
		form.appendChild(hiddenField);
		document.body.appendChild(form);
		window.open('', 'mappreview', 'fullscreen=no,height=550,width=1000,menubar=no,scrollbars=no,status=no,titlebar=no,toobar=no');
		form.submit();
		return false;
	});

	function setRoutes() {
		$routesField.val(JSON.stringify($routes.children().map(function(){
			var $r = $(this);
			return {
				id: $r.data('id'),
				points: $r.find('.dd-item').map(function(i){
					var $dd = $(this);
					$dd.find('.num').text(i + 1);
					return {
						id: $dd.data('id')
					};
				}).toArray(),
				active: $r.find('.ibox-tools input').prop('checked')
			};
		}).toArray()));
	}

	function addRoute(route, open) {
		open = !!open;
		var list = '';
		for (var j = 0; j < route.points.length; j++) {
			var pd = huntPointsData[route.points[j].id];
			list += '<li class="dd-item" data-id="' + route.points[j].id + '"><div class="dd-handle"><span class="num">' + (j + 1) + '</span>: ' + huntPointsIndex[pd.i] + ': ' + renderText(pd.p[1]) + '<span>&nbsp; / &nbsp;' + renderText(pd.q[1]) + '</span><i class="uk-nestable-nodrag fa fa-flag' + (pd.s ? '' : '-o') + '"></i></div></li>';
		}
		var $box = $(
			'<div data-id="' + route.id + '" data-oh="' + route.active_oh + '" class="ibox float-e-margins' + (open ? '' : ' margin-bottom') + '">' +
				'<div class="ibox-title">' +
					'<h5><a href="javascript:;" class="collapse-link">Route #<span>' + ($routes.children().length + 1) + '</span></a></h5>' +
					'<div class="ibox-tools">' +
						'<a href="javascript:;" class="btn btn-primary btn-sm map-preview" style="margin:-6px 15px 0 0">Map Preview</a>' +
						'<label class="checkbox-inline" for="inlineCheckbox' + generalCounter + '"><input type="checkbox" value="1" id="inlineCheckbox' + generalCounter + '"' + (route.active ? ' checked' : '') + '> Active </label>' +
						'<a href="#" class="collapse-link">' +
							'<i class="fa fa-chevron-' + (open ? 'up' : 'down') + '"></i>' +
						'</a>' +
						'<a href="#" class="close-link">' +
							'<i class="fa fa-times"></i>' +
						'</a>' +
					'</div>' +
				'</div>' +
				'<div class="ibox-content' + (open ? '' : ' collapse') + '">' +
					'<div>' +
						'<div class="dd nestable">' +
							//'<ol class="dd-list"></ol>' +
							'<ol class="dd-list">' +
								list +
							'</ol>' +
						'</div>' +
						'<div style="text-align:center"><br><a href="javascript:;" class="btn btn-danger collapse-link">Hide</a></div>' +
					'</div>' +
				'</div>' +
			'</div>'
		).appendTo($routes);
		$box.find('input').change(setRoutes);
		$box.find('.nestable.dd').nestable({
			maxDepth: 1,
			group: ++generalCounter
		}).on('change', setRoutes);
		if (open) {
			$('html, body').animate({
				scrollTop: $box.offset().top
			}, 400);
		}
		setRoutes();
	}
});