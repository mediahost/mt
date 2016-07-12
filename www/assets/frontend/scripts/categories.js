var Categories = function () {

	var $categoriesMenu = $('#categories');

	var hideSubcategories = function () {

		if ($categoriesMenu.length && $categoriesMenu.attr('data-active-categories')) {
			var active = JSON.parse($categoriesMenu.attr('data-active-categories'));
			$.each(active, function (id) {
				$('[data-category="' + id + '"]').each(function (id, el) {
					var $el = $(el);
					$el.addClass('active');
					$el.children('.category-filters').show();
				});
			});
		}

	};

	return {
		init: function () {
			hideSubcategories();
		}
	};

}();
