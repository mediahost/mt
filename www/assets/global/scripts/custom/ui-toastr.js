var UIToastr = function () {

	return {
		//main function to initiate the module
		init: function () {

			var toastReady = $('.flash.toast-ready');
			toastReady.each(function () {
				toastr.options = {
					closeButton: true,
					timeOut: 0,
					positionClass: "toast-top-right",
					showEasing: "swing",
					hideEasing: "linear",
					showMethod: "fadeIn",
					hideMethod: "fadeOut"
				};

				var shortCutFunction = "info";
				if ($(this).attr("data-type") && $(this).attr("data-type").length) {
					shortCutFunction = $(this).attr("data-type");
				}

				if ($(this).attr("data-timeout")) {
					toastr.options.timeOut = $(this).attr("data-timeout");
				}

				toastr[shortCutFunction]($(this).html());
			});

		}

	};

}();
