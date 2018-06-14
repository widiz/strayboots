$(function(){
	
	$(".select2-auto").select2({
		placeholder: "Please choose one",
		allowClear: true
	});
	var hours = $('#fieldHours'), clockpickers;
	clockpickers = $('.clockpicker').clockpicker().find('input').change(function(){
		hours.val($.map(clockpickers ,function(o){
			return o.value;
		}).join('-'));
	});

	var $images = $('#images'),
		$imagesField = $('#fieldImages');
	$images.on('click', '.img-preview a', function(){
		var $parent = $(this).parent();
		try {
			var img = $parent.data('img');
			if (img) {
				var imagesJson = $imagesField.val();
				imagesJson = JSON.parse(imagesJson);
				var idx = imagesJson.indexOf(img);
				if (idx >= 0) {
					imagesJson.splice(idx, 1);
					$imagesField.val(JSON.stringify(imagesJson));
				} else {
					throw '';
				}
			}
			$parent.parent().remove();
		} catch(E) { }
		return false;
	});
	$images.find('a.btn').click(function(){
		var $x = $(
			'<div class="col-sm-3">' +
				'<input type="file" name="img2[]" accept="image/*" style="display:none">' +
				'<div class="img-preview"><a href="#" style="display:block"><i class="fa fa-times"></i></a></div>' +
			'</div>'
		).insertBefore($(this).parent());
		$x.find('input').change(function(){
			if (this.files.length === 0) {
				$x.find('a').click();
			} else {
				var reader = new FileReader();
				reader.readAsDataURL(this.files[0]);
				reader.onload = function(){
					$x.find('.img-preview').css('background-image', 'url(' + reader.result + ')');
				};
				reader.onerror = function (error) {
					$x.find('a').click();
				};
			}
		}).click();
	});

	var $map = $('#products-map'),
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
			$('#products-map').slideUp(200);
			return;
		}

		/*if ($long.val() || $lat.val()) {
			if (!confirm("Do you want to overwrite coordinates?"))
				return;
		}*/

		geocoder.geocode({'address': address}, function(results, status){
			if (!(status == google.maps.GeocoderStatus.OK && results.length && results[0].geometry)) {
				$('#products-map').slideUp(200);
			} else {
				$('#products-map').slideDown(200, function(){
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