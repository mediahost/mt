var AppContent = function () {

	var handleLoadingButton = function () {
		$('.loading-btn').click(function (e) {
			var btn = $(this);
			btn.button('loading');
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
			handleOffline(3);
		}
	};

}();
