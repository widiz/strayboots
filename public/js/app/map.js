$(function(){
	var $contentWrapper = $('body.controller-map > .main-section > .content-wrapper'),
		$window = $(window);
	$window.resize(function(){
		$contentWrapper.height($window.height());
	}).trigger('resize');
});
//preload font
$('body').append('<div style="font:500 11px Roboto;position:absolute;top:-150px;visibility:hidden;z-index:-1">x</div>');
var markerImage = new Image();
markerImage.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACoAAAA5CAMAAAB3X0lcAAAAilBMVEUAAADFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkLFQkK1PpnoAAAALXRSTlMABflE3s/JGxUP6+fUw3YK2atpLLyMhYBbUUwx76SYkjkkIsC4nW9kPl5dWSD8vbTuAAABRUlEQVR4AbXV1xLaMBQE0JV7L2DjQu+k7P//XmImDNhY9n3Jed6RViONLnqUZybbOLIs+7JbrH1o+Y8y45sR344KY1Sbcygol/h2Kjlms0gxYEbU2J7QUxvUsnslfhicYH1kTYOTMhf/uBZnOCme0pyz9gqdhgItAHgZBSwPgEmRBki3FIl9uBvKrNFQKMGKQjlyCjmIKRTBlked/xC15dECBYV2uFJogYRCazwoY/vwQopUCulOur/0adseADeUnR+ASjgvdNH5HcgW7SScE7joSNomeLlxWuThxXc4qYb0Kzz7eFNX6gUHdCQVVuhrN9SIUwyUHBeYGFIXjqrwbZlxRKEwojb0w2VoxS8/oVFwYA+dk8WeWEHL5KfgiAkVP/zCpLx/oVq9KXbGnEPIJ/uIWa3Bv8IDBGqSRgORO3mHULXHiD/98xjFIo82AwAAAABJRU5ErkJggg==';
var markerImageW = new Image();
//markerImageW.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACoAAAA5CAMAAAB3X0lcAAAAkFBMVEUAAADi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uKl5OkLAAAAL3RSTlMA+QPPWyLo1MJ2RQ8M7t3ZqxrjyseMhYBmUUwxLBQIvKSYkmpBORbyvridbx47Kfo9g84AAAFLSURBVBgZjcEFYsJQFATAjbsiwd2h3fvfrmghIT95MyiZTX1v4Tp5bByX63EApeCnl/BNc8/WDHX0UcqqqDfBN6vHOvt1iArfocLCQompUcmY4IOpsUH8i3++xkZJhqcsZ4tOiLswZauVjpshBUa42iUUiHcANhQZAuGCIm6AbE+ZMYYU8tCnUIqUQl10KeTAoJCDDoUcdClkoEOhOQ4UWuJEoQE8Co2xoYwRYGdTpNAxW1JkDGBDCWMKIMspMMCV7rGdfcGNFbHVGg8e29gXPGQ2W3h4ObOZM8VL0GEjE2++xgaHEG/6iWrRFp+CLpX6KBtFVHBDlOk91rN9VM2OrFXg2yRhjbmOGqbGL/EEtfr8YkJhzooVVKyYJa4OJZ+fIgsNCn4w0Sjlvz6aTRM+uWizzXlnWGg10nhlbyFgktSGEBmQAwgVK9T4A2ljJi9AoD9QAAAAAElFTkSuQmCC';
markerImageW.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACoAAAA5CAMAAAB3X0lcAAAAnFBMVEUAAADx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fGipO1bAAAAM3RSTlMABflEGxUP1MJ2Cubd2Wks7dDMyYyFgFFMMfHpxryspJZZOSQg4b64qZuRb2RbPp1eXSJQjGAgAAABTUlEQVRIx7XV13aCQBQF0DM0EcGIoqCx99hSzv//WzRLlw4wcF+yn++acssMNMq1mzO/sQnj3bw99GDkXZKIT5Z/XCmUUdmUeZ3kDUXrhGVG7QA5doMGsx9ozhaNYu0QXxYrhC+xtsVKkYM7Z8MavfvdgilrHRRuUgpkuHIjCoQuAJsiKRDMKOJ7cEaUGSKlUBN9Cu0xpdAYYwo10P2X0B6FuvLQCbYUmuOTQm00KS7shTKxB7dFkYFC8CHcX9zaXReA05Ld/0pJctBycLPuCBcFBKl9d3AjOW0TD8e6/nPx4NW01wLSp3Dr4UlVlayzxCtvTKM+dNmIBn6AnMSUUht5amdoPhS9RaXDp1DibJV+LqX6LPiGwaQ40CbrkBpfwcjWy7RChUGhS8z2WkGN9F/MR53lfSTiFWpl1l/plxBYWKSVQuREniA0OKDEL4WkPfOGRYrZAAAAAElFTkSuQmCC';
var markerImageG = new Image();
markerImageG.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACoAAAA5CAMAAAB3X0lcAAAAh1BMVEUAAABP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRP3IRJF+J2AAAALHRSTlMABflE588bFQ/t1MJ2Ct3Zq2csIsrHvIyGgG1dUUwx9KSYklk54bidP74kIFcMweEAAAFHSURBVHgBjdXnkptAEATgXnIO4gCBQEGnC7b7/Z/POtkqRFiY73fXDNR21WBEuWZ0DoOPxP68tEcPWt53nXJghG2hsET1Gaf8eoe5ouaSUx5jwgyocS4wcjCoZe+kSTJ5yZonrkod/Od8cMNbjIc446arwo+OAj3u3JQCiQvApEgHxGeKhB6cE2WO6CgUYU+hDBmFSpQUCmDLo2/yaEkhWz61QkWhC74olCOi0BHflLE9uBZFGoX4ItwvrrbtAnAs2f/fqYjbLAc//vjc1OKfSDhU9LURnlquC1w8eRv1esfANLjil4eB+qKef8Mrr6TWHmO9T40wxkStW29iSn1yUYO5XcoFlcKCg6E/LlN7zhygUXHiCp0i4UiooGXylf8bK5pZS/Qy/YNODVcsxJabxQe7wKbe4J11g8C7QRodRHIyh1BzxYK/ergS7k2oRNEAAAAASUVORK5CYII=';
function initMap(){

	var drawMarkerImage = function(count, icon) {
		count = count || '';

		var image, imageObj;
		var color = "#FFF";
		switch (icon || '') {
			case 'w':
				image = markerImageW.src;
				imageObj = markerImageW;
				color = '#000';
				break;
			case 'g':
				image = markerImageG.src;
				imageObj = markerImageG;
				break;
			default:
				image = markerImage.src;
				imageObj = markerImage;
		}
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

				canvas.width = marker.width;
				canvas.height = marker.height;

				ctx.drawImage(imageObj, 0, 0, canvas.width, canvas.height);
				ctx.font =  marker.font;
				ctx.fillStyle  = color;

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

	var map = new google.maps.Map(document.getElementById('map'), {
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
		flightPlanCoordinates = [];
	for (var i = 0; i < window._map.length; i++) {
		var m = window._map[i];
		var long = parseFloat(m.longitude),
			lat  = parseFloat(m.latitude);
		//console.log(m.idx, long, lat, m.name);
		var icon = m.id > 0 && typeof window.strategyMap[m.id] === 'string' ? window.strategyMap[m.id] : null;
		var markersettings = {
			position: {lat: lat, lng: long},
			map: map,
			icon: drawMarkerImage(parseInt(m.idx) + 1, icon),
			title: m.name,
			/*icon: 'icon.png',
			label: m.idx*/
		};
		//if (i === 0)
		//	markersettings.icon = '/img/beachflag.png';
		new google.maps.Marker(markersettings);
		window._map[i] = new google.maps.LatLng(lat, long);
		flightPlanCoordinates.push(window._map[i]);
		bounds.extend(window._map[i]);
	}
	map.fitBounds(bounds);
	if (window.strategyMap.length === 0) {
		var flightPath = new google.maps.Polyline({
			path: flightPlanCoordinates,
			geodesic: true,
			strokeColor: '#F39C12',
			strokeOpacity: 0.8,
			strokeWeight: 2
		});
		flightPath.setMap(map);
	}

	// Try HTML5 geolocation.
	if (navigator.geolocation) {
		var searchCircle = new google.maps.Circle({
			fillColor: '#c0e4dd',
			strokeColor: '#f15f22',
			fillOpacity: 0.5,
			radius: 1500,
			map: map,
			visible: false
		});
		var searchMarker = new google.maps.Marker({
			map: map,
			title: "Position",
			//icon: 'icon.png',
			label: 'P',
			visible: false
		});
		navigator.geolocation.watchPosition(function(position){
			searchCircle.setVisible(true);
			searchCircle.setRadius(position.coords.accuracy);
			searchMarker.setVisible(true);
			position = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
			searchCircle.setCenter(position);
			searchMarker.setPosition(position);
		}, function(){
			searchCircle.setVisible(false);
			searchMarker.setVisible(false);
		}, {
			enableHighAccuracy: true,
			timeout: 1e4,
		});
	}
}