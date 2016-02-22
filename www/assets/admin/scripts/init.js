jQuery(document).ready(function () {
	Metronic.init(); // init metronic core componets
	Layout.init(); // init layout
	$.nette.init(); // https://github.com/vojtech-dobes/nette.ajax.js

	// Global components
	GlobalCustomInit.init();

	// components
	UIToastr.init();
	Fullscreen.init();
	AppContent.init();
	
	Buyout.init('[data-typeahead-url]');
	Newsletter.init();
});

$('.modal.ajax').on('loaded.bs.modal', function (e) {
	GlobalCustomInit.onReloadModalEvent();
});

$.nette.ext('netteAjax', {
	complete: function (payload, t, params) {
		if (params.nette.el.attr('data-dismiss-after')) {
			params.nette.el.closest('.modal').find('[data-dismiss="modal"]').click();
		}
		for (i in payload.snippets) {
			switch (String(i)) {
				case 'snippet--flashMessages':
					break;
				default:
					GlobalCustomInit.onReloadGridoEvent();
					GlobalCustomInit.onReloadModalEvent();
					break;
			}
		}
	}
});

