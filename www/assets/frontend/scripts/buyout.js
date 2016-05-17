
var Buyout = Buyout || {};

Buyout.init = function () {
	$(document).on('click', '#buyout input[type="radio"]', function () {
		$('#buyout input[name=recalculate]').click();
	});

	$(document).on('click', '#buyout .is-new-question a', function (e) {
		e.preventDefault();

		var checkbox = $('#buyout .is-new-checker input');
		var questionBlock = $('#buyout .question-block');
		var partPriceBlock = $('#buyout .part-price');
		var fullPriceBlock = $('#buyout .full-price');

		var isNew = $(this).hasClass('new');

		checkbox.prop('checked', isNew);
		$.uniform.update();

		if (isNew) {
			questionBlock.hide();
			partPriceBlock.hide();
			fullPriceBlock.show();
		} else {
			questionBlock.show();
			partPriceBlock.show();
			fullPriceBlock.hide();
		}

		$('#buyout .is-new-question a').removeClass('active');
		$(this).addClass('active');
	});
};