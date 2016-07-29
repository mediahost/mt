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
			if (!settings.nette) {
				return;
			}

			var moveTargetAttr = 'data-target-move';
			this.moveTarget = $(settings.nette.el.attr(moveTargetAttr));

			var loadingTargetAttr = 'data-target-loading';
			var parentPortlet = null;
			if (settings.nette.form && settings.nette.form.length) {
				var form = settings.nette.form;
				if (form.attr(loadingTargetAttr)) {
					this.element = $(form.attr(loadingTargetAttr));
					var parentPortlet = this.element;
				} else {
					this.element = settings.nette.form;
					var parentPortlet = this.element.closest('.portlet');
				}
			} else if (settings.nette.el.attr(loadingTargetAttr)) {
				var parentPortlet = $(settings.nette.el.attr(loadingTargetAttr));
			} else {
				var parentPortlet = settings.nette.el.closest('.portlet');
			}
			if (parentPortlet && parentPortlet.length) {
				this.element = parentPortlet;
			}
			var options = {
				target: this.element
			};
			if (typeof(Frontend) === 'object') {
				options.iconOnly = true;
			} else {
				options.animate = true;
			}
			if (this.element && this.element.hasClass('loadingNoOverlay')) {
				options.overlayColor = 'none';
			}
			if (typeof(Frontend) === 'object') {
				Frontend.blockUI(options);
			} else if (typeof(Metronic) === 'object') {
				Metronic.blockUI(options);
			}
		},
		complete: function () {
			if (typeof(Frontend) === 'object') {
				Frontend.unblockUI(this.element);
			} else if (typeof(Metronic) === 'object') {
				Metronic.unblockUI(this.element);
			}
			if (this.moveTarget.length) {
				var topPosition = this.moveTarget.offset().top - 20;
				$('html, body').stop().animate({scrollTop:topPosition}, 500, 'swing');
			}
		}
	}, {
		element: undefined,
		moveTarget: undefined
	});

})(jQuery);
