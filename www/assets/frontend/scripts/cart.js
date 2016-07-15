var Cart = function () {

	var paymentsInputSelector = '.cart-content.payments-data .radio-list .i-radio input';

	var checkRadio = function (radio) {
		if (radio) {
			actualizeRadio(radio);
		} else {
			$(paymentsInputSelector).each(function (e) {
				actualizeRadio($(this));
			});
		}
	};

	var actualizeRadio = function (radio) {
		if (radio.is(':checked')) {
			radio.closest('label').addClass('active');
		} else if (!radio.is(':enabled')) {
			radio.closest('label').addClass('disabled');
		} else {
			radio.closest('label').removeClass('active').removeClass('disabled');
		}
	};

	var cartGoodsList = function () {
		$('.showDiscountInput').on('click', function (e) {
			e.preventDefault();
			$('#discountInput').css('display', 'table');
			$(this).hide();
		});
	};

	var cartPayments = function () {
		$(document).on('change', paymentsInputSelector, function (e) {
			checkRadio();
		});
	};

	var cartAddress = function () {
		$('.cart-continue a.send-button').on('click', function (e) {
			e.preventDefault();
			$('.cart-content.personal-data input.send-button').click();
		});
	};

	return {
		init: function () {
			cartGoodsList();
			cartPayments();
			cartAddress();
		}
	};

}();
