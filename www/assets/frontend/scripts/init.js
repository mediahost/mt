jQuery(document).ready(function () {
	Metronic.init(); // init metronic core componets
	Layout.init(); // init layout
	Layout.initUniform();
	$.nette.init(); // https://github.com/vojtech-dobes/nette.ajax.js
	
	Layout.initTwitter();
	Layout.initSliderRange();
	Layout.initOWL();
	Layout.initImageZoom();
	LayersliderInit.initLayerSlider();

	// special for pages
	Login.init();
	
	// Global components
	GlobalCustomInit.init();
});

$.nette.ext('netteAjax', {
	complete: function () {
		GlobalCustomInit.onReloadProductList();
	}
});
