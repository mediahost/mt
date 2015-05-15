var HtmlEditors = function () {

	var handleWysihtml5 = function () {
		if (!jQuery().wysihtml5) {
			return;
		}

		if ($('.wysihtml5').size() > 0) {
			$('.wysihtml5').wysihtml5({
				"stylesheets": [basePath + "/assets/global/plugins/bootstrap-wysihtml5/wysiwyg-color.min.css"]
			});
		}
	};

	return {
		//main function to initiate the module
		init: function () {

			handleWysihtml5();

		}

	};

}();
