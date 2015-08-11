
var Buyout = Buyout || {};

Buyout.init = function () {
	$(document).on('click', '#buyout input[type="radio"]', function () {
		$('#buyout input[name=recalculate]').click();
	});
};