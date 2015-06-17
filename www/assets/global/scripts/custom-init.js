var GlobalCustomInit = function () {

	return {
		init: function () {
			ComponentsDropdowns.init();
			ComponentsNoUiSliders.init();
			ComponentsFormTools.init();
			ComponentsPickers.init();
			Maps.init();
			UITree.init();

			GridoStart.init();
			if (typeof MultipleFileUpload != 'undefined') {
				MultipleFileUpload.init();
			}
		},
		onReloadGridoEvent: function () {
			Metronic.init();
		},
		onReloadModalEvent: function () {
			ComponentsDropdowns.init(); // init form components after ajax load
			Nette.initAllForms(); // reinit all nette forms
		}
	};

}();