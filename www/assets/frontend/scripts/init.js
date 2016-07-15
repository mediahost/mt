jQuery(document).ready(function () {
	$.nette.init(); // https://github.com/vojtech-dobes/nette.ajax.js

	Frontend.init();
	Categories.init();
	Cart.init();
});

$.nette.ext('netteAjax', {
	before: function (jqXHR, settings) {
	},
	complete: function (payload, t, params) {
		Cart.init();
		Frontend.afterAjaxInit();
	}
});
