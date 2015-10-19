var GridoStart = function () {

	var handleStart = function () {

		$('.edit .sendOnChange').on('change', function () {
			$(this).closest('form').submit();
		});

	};

	return {
		init: function () {
			handleStart();
		}
	};

}();
