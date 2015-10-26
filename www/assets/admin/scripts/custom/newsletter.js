
var Newsletter = Newsletter || {};

Newsletter.init = function () {
	var e = document.getElementById('new-newsletter-data');

	if (e) {
		var data = JSON.parse(e.textContent || e.innerHTML);

		$(document).on('change', '#new-newsletter-locale', function () {
			if ($('#new-newsletter-recipients').val() === 'u') {
				$('#new-newsletter-count').text(data[$('#new-newsletter-locale').val()]);
			}
		});
	}


	$(document).on('change', '#new-newsletter-recipients', function () {
		$('#new-newsletter-validate').click();
	});
};