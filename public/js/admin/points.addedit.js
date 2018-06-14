$(function(){
	
	$(".select2-auto").select2({
		placeholder: "Please choose one",
		allowClear: true
	});
	var hours = $('#fieldHours'), clockpickers;
	clockpickers = $('.clockpicker').clockpicker().find('input').change(function(){
		hours.val($.map(clockpickers ,function(o) {
			return o.value;
		}).join('-'));
	});

	var $map = $('#points-map'),
		$long = $('#fieldLongitude'),
		$lat = $('#fieldLatitude'),
		$address = $('#fieldAddress');

	var geocoder = new google.maps.Geocoder(),
		map, marker, point;

	$address.blur(function(){
		var address = $address.val();
		if (!address/* || $long.val() || $lat.val()*/) {
			$long.val(0);
			$lat.val(0);
			$('#points-map').slideUp(200);
			return;
		}

		/*if ($long.val() || $lat.val()) {
			if (!confirm("Do you want to overwrite coordinates?"))
				return;
		}*/

		geocoder.geocode({'address': address}, function(results, status){
			if (!(status == google.maps.GeocoderStatus.OK && results.length && results[0].geometry)) {
				$('#points-map').slideUp(200);
			} else {
				$('#points-map').slideDown(200, function(){
					if (map) {
						google.maps.event.trigger(map, "resize");
						if (marker) {
							point = marker.getPosition();
							map.panTo(point);
							map.setCenter(point);
						}
					}
				});

				point = results[0].geometry.location;

				$lat.val(parseFloat(point.lat().toFixed(8)));
				$long.val(parseFloat(point.lng().toFixed(8)));

				map = map || new google.maps.Map($map.get(0), {
					zoom: 17,
					scrollwheel: false,
					center: point
				});
				if (marker) {
					map.panTo(point);
					map.setCenter(point);
					marker.setPosition(point);
				} else {
					marker = new google.maps.Marker({
						position: point,
						map: map,
						draggable: true,
						animation: google.maps.Animation.DROP
					});
					google.maps.event.addListener(marker, 'dragend', function(){
						point = marker.getPosition();
						geocoder.geocode({
							'latLng': point
						}, function (results, status) {
							if (status == google.maps.GeocoderStatus.OK && results.length && results[0].formatted_address)
								$address.val(results[0].formatted_address);
						});
						map.panTo(point);
						map.setCenter(point);
						$lat.val(parseFloat(point.lat().toFixed(8)));
						$long.val(parseFloat(point.lng().toFixed(8)));
					});
				}
			}
		});
	}).trigger('blur');

	new google.maps.places.Autocomplete($address.get(0));

});