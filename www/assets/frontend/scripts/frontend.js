var Frontend = function () {

	return {
		init: function () {
			$(document).on('ifChanged', 'input.i-check', function (e) {
				$(this).change();
			});
			// $("#homecreditCalc").fancybox({
			// 	'height': '90%'
			// });

			// $('#frm-products-filterForm').removeClass('in');

			// $(document).on('click', '.alert-auto-dismiss', function () {
			// 	$(this).fadeOut();
			// });

			// if (loginError) {
			// 	$('#signInModal').modal('show');
			// }
		},
		afterComplete: function () {
			// setTimeout(function () {
			// 	$('.alert-auto-dismiss').fadeOut();
			// }, 2000);
		}
	};

}();
