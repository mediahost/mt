var Frontend = function () {

	return {
		init: function () {
			$('#frm-products-filterForm').removeClass('in');
			$(document).on('click', '.alert-auto-dismiss', function () {
				$(this).fadeOut();
			});
			if (loginError) {
				$('#signInModal').modal('show');
			}
		},
		afterComplete: function () {
			setTimeout(function () {
				$('.alert-auto-dismiss').fadeOut();
			}, 2000);
		}
	};

}();
