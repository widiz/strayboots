var markerImage = new Image();
markerImage.src = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACoAAAA5CAMAAAB3X0lcAAAAilBMVEUAAADFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkK1PpnoAAAALXRSTlMABflE3s/JGxUP6+fUw3YK2atpLLyMhYBbUUwx76SYkjkkIsC4nW9kPl5dWSD8vbTuAAABRUlEQVR4AbXV1xLaMBQE0JV7L2DjQu+k7P//XmImDNhY9n3Jed6RViONLnqUZybbOLIs+7JbrH1o+Y8y45sR344KY1Sbcygol/h2Kjlms0gxYEbU2J7QUxvUsnslfhicYH1kTYOTMhf/uBZnOCme0pyz9gqdhgItAHgZBSwPgEmRBki3FIl9uBvKrNFQKMGKQjlyCjmIKRTBlked/xC15dECBYV2uFJogYRCazwoY/vwQopUCulOur/0adseADeUnR+ASjgvdNH5HcgW7SScE7joSNomeLlxWuThxXc4qYb0Kzz7eFNX6gUHdCQVVuhrN9SIUwyUHBeYGFIXjqrwbZlxRKEwojb0w2VoxS8/oVFwYA+dk8WeWEHL5KfgiAkVP/zCpLx/oVq9KXbGnEPIJ/uIWa3Bv8IDBGqSRgORO3mHULXHiD/98xjFIo82AwAAAABJRU5ErkJggg==";
$('body').append('<div style="font:500 11px Roboto;position:absolute;top:-150px;visibility:hidden;z-index:-1">x</div>');
$(function(){

	var drawMarkerImage = function(count) {
		count = count || '';

		var image = markerImage.src;
		try {
			var canvas = document.createElement("canvas");
			var ctx = canvas.getContext("2d");

			var marker = {
				yPos: 26,
				width: 39,
				height: 53,
				font: '500 16px Roboto'
			};
			var marker_label = '' + count;

			if (canvas.getContext) {
				var imageObj = markerImage;

				canvas.width = marker.width;
				canvas.height = marker.height;

				ctx.drawImage(imageObj, 0, 0, canvas.width, canvas.height);
				ctx.font =  marker.font;
				ctx.fillStyle  = "#FFF";

				var metrics = ctx.measureText(marker_label);
				var textWidth = metrics.width;
				var xPosition = (marker.width - textWidth) / 2;
				var yPosition = marker.yPos;

				ctx.fillText(marker_label, xPosition, yPosition);

				image = canvas.toDataURL('image/png');
			}
			$(canvas).remove();
		} catch(E) {}

		return image;
	};

	var $map = $('#themap');
	window.gmap(function(){
		var data = window.data || decodeURIComponent(document.location.search.replace(/^\?/, '')) || [];
		if (typeof data == 'string') {
			try {
				data = JSON.parse(data);
			} catch(E) {
				data = [];
			}
		}
		var map = new google.maps.Map($map.empty().get(0), {
			center: {lat: 40.748, lng: -73.985},
			zoom: 13,
			zoomControlOptions: {
				position: google.maps.ControlPosition.RIGHT_CENTER
			},
			streetViewControlOptions: {
				position: google.maps.ControlPosition.RIGHT_TOP
			}
		});


		var bounds = new google.maps.LatLngBounds(),
			flightPlanCoordinates = [],
			infoWindows = {};
		for (var i = 0; i < data.length; i++) {
			var m = data[i];
			var long = parseFloat(m.longitude),
				lat  = parseFloat(m.latitude);
			//console.log(m.idx, long, lat, m.title);
			(function(j){
				var marker = new google.maps.Marker({
					position: {lat: lat, lng: long},
					map: map,
					icon: drawMarkerImage(m.label)
					//label: m.label,
					/*icon: 'icon.png',
					label: m.idx*/
				});
				if (m.info) {
					infoWindows[j] = new google.maps.InfoWindow({
						content: m.info
					});
					marker.addListener('click', function() {
						infoWindows[j].open(map, marker);
					});
				}
			})(i);
			data[i] = new google.maps.LatLng(lat, long);
			flightPlanCoordinates.push(data[i]);
			bounds.extend(data[i]);
		}
		map.fitBounds(bounds);
		var flightPath = new google.maps.Polyline({
			path: flightPlanCoordinates,
			geodesic: true,
			strokeColor: '#F39C12',
			strokeOpacity: 0.8,
			strokeWeight: 2
		});
		flightPath.setMap(map);

        map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push($('#map-legend').show().get(0));
	});
})