var AppContent = function () {

	var handleLoadingButton = function () {
		$('.loading-btn').click(function (e) {
			var btn = $(this);
			btn.button('loading');
		});
	};

	var handleCheckboxTarget = function () {
		$('input[type=checkbox]').on('change', function (e) {
			var $target = $(e.target);
			if ($target.is(':checked') && $target.data('targetOn')) {
				window.location.href = $target.data('targetOn');
			} else if($target.data('targetOff')) {
				window.location.href = $target.data('targetOff');
			}
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
			handleCheckboxTarget();
			// handleOffline(3);
		}
	};

}();
