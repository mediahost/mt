jQuery(document).ready(function () {
	Metronic.init(); // init metronic core componets
	Layout.init(); // init layout
	Layout.initUniform();
	$.nette.init(); // https://github.com/vojtech-dobes/nette.ajax.js

	// Global components
	GlobalCustomInit.init();

	Layout.initTwitter();
	Layout.initSliderRange();
	Layout.initOWL();
	Layout.initImageZoom();
	LayersliderInit.initLayerSlider();
	
	Frontend.init();
	Buyout.init();
	Cart.init();

	// special for pages
	Login.init();
	Service.init();
});

$.nette.ext('netteAjax', {
	complete: function (payload, t, params) {
		if (params.nette.el.attr('data-dismiss-after')) {
			params.nette.el.closest('.modal').find('[data-dismiss="modal"]').click();
		}
		GlobalCustomInit.onReloadProductList();
		Layout.initOWL();
		Frontend.afterComplete();
	}
});
