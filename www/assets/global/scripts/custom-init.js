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
			HtmlEditors.init();
			Maps.init();
		},
		initFormComponents: function () {
			Metronic.initComponents();
			ComponentsFormTools.init();
			ComponentsDropdowns.init();
			ComponentsNoUiSliders.init();
			ComponentsPickers.init();
			ComponentsDropdowns.init();
			HtmlEditors.init();
		},
		onReloadGridoEvent: function () {
			Metronic.init();
		},
		onReloadModalEvent: function () {
			this.initFormComponents();
			Nette.initAllForms();
		},
		onReloadProductList: function () {
			Layout.initUniform();
			Layout.initSliderRange();
			ComponentsFormTools.init();
		},
		onChangeJSTree: function () {
			this.initFormComponents();
		}
	};

}();