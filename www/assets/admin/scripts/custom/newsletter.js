
var Newsletter = Newsletter || {};

Newsletter.init = function () {
	$(document).on('change', '#new-newsletter-recipients', function () {
		$('#new-newsletter-validate').click();
	});
};