var Maps = function () {

	var handleMaps = function () {

		$('.gmaps').each(function () {
			var id = $(this).attr('id');
			var name = $(this).attr('data-name');
			var address = $(this).attr('data-address');
			var addressDesc = $(this).attr('data-address-desc');
			var notFound = $(this).attr('data-not-found');
			var mapBlock = $('#' + id).parent();
			
			if (!address) {
				mapBlock.hide();
			}

			map = new GMaps({
				div: '#' + id,
				lat: 0,
				lng: 0,
				title: name
			});
			GMaps.geocode({
				address: address,
				callback: function (results, status) {
					var marker;
					if (status == 'OK') {
						var latlng = results[0].geometry.location;
						map.setCenter(latlng.lat(), latlng.lng());
						marker = map.addMarker({
							title: name,
							lat: latlng.lat(),
							lng: latlng.lng(),
							infoWindow: {
								content: '<b>' + name + '</b><br>' + addressDesc
							}
						});
						mapBlock.show();
					} else {
						marker = map.addMarker({
							title: notFound,
							lat: 0,
							lng: 0,
							infoWindow: {
								content: '<b>' + name + '</b><br>' + notFound
							}
						});
						mapBlock.hide();
					}
					marker.infoWindow.open(map, marker);
				}
			});
		});

	};

	return {
		init: function () {
			handleMaps();
		}
	};

}();
