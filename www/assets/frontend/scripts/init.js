jQuery(document).ready(function () {
	Metronic.init(); // init metronic core componets
	Layout.init(); // init layout
	Layout.initUniform();
	$.nette.init(); // https://github.com/vojtech-dobes/nette.ajax.js
	
	Layout.initTwitter();
	Layout.initSliderRange();

	// special for pages
	Login.init();
	
	// Global components
	GlobalCustomInit.init();
});
