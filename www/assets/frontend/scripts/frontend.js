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
			var $categoryMenu = $('.categories-menu');
			if ($categoryMenu.length && $categoryMenu.attr('data-active-categories')) {
				var active = JSON.parse($categoryMenu.attr('data-active-categories'));
				$.each(active, function (id) {
					$('[data-category="' + id + '"], [data-parent-category="' + id + '"]').each(function (id, el) {
						var $parent = $(el).closest('.submenu.hidden');
						$parent.addClass('active');
						$parent.removeClass('hidden');
						$(el).removeClass('hidden');
						$(el).addClass('active');
					});
				});
			}
		},
		afterComplete: function () {
			setTimeout(function () {
				$('.alert-auto-dismiss').fadeOut();
			}, 2000);
		}
	};

}();
