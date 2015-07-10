//(function ($, undefined) {
//
//	$.nette.ext('spinner', {
//		init: function () {
//			this.spinner = this.createSpinner();
//			this.spinner.appendTo('body');
//		},
//		start: function () {
//			this.spinner.show(this.speed);
//		},
//		complete: function () {
//			this.spinner.hide(this.speed);
//		}
//	}, {
//		createSpinner: function () {
//			return $('<div>', {
//				id: 'ajax-spinner',
//				css: {
//					display: 'none'
//				}
//			});
//		},
//		spinner: null,
//		speed: undefined
//	});
//
//})(jQuery);

(function ($, undefined) {

	$.nette.ext('loader', {
		start: function (jqXHR, settings) {
			var targetAttr = 'data-target-loading';
			var parentPortlet = null;
			if (settings.nette.form && settings.nette.form.length) {
				var form = settings.nette.form;
				if (form.attr(targetAttr)) {
					this.element = $(form.attr(targetAttr));
					var parentPortlet = this.element;
				} else {
					this.element = settings.nette.form;
					var parentPortlet = this.element.closest('.portlet');
				}
			} else if (settings.nette.el.attr(targetAttr)) {
				var parentPortlet = $(settings.nette.el.attr(targetAttr));
			} else {
				var parentPortlet = settings.nette.el.closest('.portlet');
			}
			if (parentPortlet && parentPortlet.length) {
				this.element = parentPortlet;
			}
			var options = {
				target: this.element,
				animate: true
			};
			if (this.element && this.element.hasClass('loadingNoOverlay')) {
				options.overlayColor = 'none';
			}
			Metronic.blockUI(options);
		},
		complete: function () {
			Metronic.unblockUI(this.element);
		}
	}, {
		element: undefined
	});

})(jQuery);
