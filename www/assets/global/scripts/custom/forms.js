var Forms = function () {

	var handleSelectSendOnChange = function () {
		// místo:
		// $('form select.sendOnChange').live('change', function() { ... });
		// lze použít lepší:
		$('body').delegate('form.sendOnChange input, form.sendOnChange select', 'change', function () {
			$(this).closest('form').submit();
		});
	};

	return {
		init: function () {
			handleSelectSendOnChange();
		}
	};

}();
