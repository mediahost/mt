var Frontend = function () {

	return {
		init: function () {
			$('#frm-products-filterForm').removeClass('in');
			$(document).on('click', '.alert-auto-dismiss', function () {
				$(this).fadeOut();
			});
		},
		afterComplete: function () {
			setTimeout(function () {
				$('.alert-auto-dismiss').fadeOut();
			}, 2000);
		}
	};

}();