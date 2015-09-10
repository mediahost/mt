var Forms = function () {

	var cartAddress = function () {
		$('.cart-continue a.send-button').on('click', function (e) {
			e.preventDefault();
			$('.cart-content.personal-data input.send-button').click();
		});
	};

	return {
		init: function () {
			cartAddress();
		}
	};

}();
