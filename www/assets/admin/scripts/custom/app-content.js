var AppContent = function () {

	var handleLoadingButton = function () {
		$('.loading-btn').click(function (e) {
			var btn = $(this);
			btn.button('loading');
		});
	};

	var handleOrderShops = function () {
		$('input[type=checkbox].shopSwitch').on('change', function (e) {
			var $target = $(e.target);
			var shopId = $target.val();
			var query = parse_url(window.location, 'query');
			var path = parse_url(window.location, 'path');
			var params = {filteredShops: {}};
			if (typeof query != 'undefined') {
				parse_str(query, params);
			}
			if (Object.keys(params.filteredShops).length === 0) {
				$('input[type=checkbox].shopSwitch').each(function (key, item) {
					if ($(item).is(':checked')) {
						params.filteredShops[key] = $(item).val();
					}
				});
			}

			var selectedKey = null;
			var isSelected = false;
			var newKey = 0;
			$.each(params.filteredShops, function (key, value) {
				if (value == shopId) {
					isSelected = true;
					selectedKey = key;
				}
				newKey = key;
			});
			newKey++;

			if ($target.is(':checked') && !isSelected) {
				params.filteredShops[newKey] = shopId;
			} else if (!$target.is(':checked') && isSelected) {
				delete params.filteredShops[selectedKey];
			}
			url = path + '?' + http_build_query(params);
			window.location.href = url;
		});
	};

	// https://github.com/HubSpot/offline
	var handleOffline = function (minutes) {
		if (typeof Offline !== 'undefined') {
//			Offline.options = {checks: {xhr: {url: '/connection-test'}}};
			Offline.options = {reconnect: {initialDelay: 7}};
			setInterval(function () {
				if (Offline.state === 'up') {
					Offline.check();
				}
			}, minutes * 1000);
		}
	};

	return {
		//main function to initiate the module
		init: function () {
			handleLoadingButton();
			handleOrderShops();
			// handleOffline(3);
		}
	};

}();
