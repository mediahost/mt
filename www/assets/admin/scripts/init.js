jQuery(document).ready(function () {
	Metronic.init(); // init metronic core componets
	Layout.init(); // init layout
	$.nette.init(); // https://github.com/vojtech-dobes/nette.ajax.js
	
	// Global components
	GlobalCustomInit.init();

	// components
	HtmlEditors.init();
	UIToastr.init();
	Fullscreen.init();

	AppContent.init();
});

$('.modal.ajax').on('loaded.bs.modal', function (e) {
	GlobalCustomInit.onReloadModalEvent();
});

$.nette.ext('netteAjax', {
	complete: function () {
		GlobalCustomInit.onReloadGridoEvent();
		GlobalCustomInit.onReloadModalEvent();
	}
});

