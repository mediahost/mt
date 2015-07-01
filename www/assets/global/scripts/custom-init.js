var GlobalCustomInit = function () {

	return {
		init: function () {
			ComponentsFormTools.init();
			ComponentsDropdowns.init();
			ComponentsNoUiSliders.init();
			ComponentsPickers.init();

			Forms.init();
			GridoStart.init();
			if (typeof MultipleFileUpload != 'undefined') {
				MultipleFileUpload.init();
			}
			
			UITree.init();
			Maps.init();
		},
		onReloadGridoEvent: function () {
			Metronic.init();
		},
		onReloadModalEvent: function () {
			ComponentsDropdowns.init(); // init form components after ajax load
			Nette.initAllForms(); // reinit all nette forms
		},
		onReloadProductList: function () {
			Layout.initUniform();
			Layout.initSliderRange();
		}
	};

}();