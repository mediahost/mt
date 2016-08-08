(function ($, undefined) {

	$.nette.ext('loader', {
		start: function (jqXHR, settings) {
			if (!settings.nette) {
				return;
			}

			var moveTargetAttr = 'data-target-move';
			this.moveTarget = $(settings.nette.el.attr(moveTargetAttr));

			var clickTargetAttr = 'data-target-click';
			this.clickTarget = $(settings.nette.el.attr(clickTargetAttr));

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
			if (this.element.hasClass('loadingAnimate') || typeof(Frontend) !== 'object') {
				options.animate = true;
			} else {
				options.iconOnly = true;
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
				$('html, body').stop().animate({scrollTop: topPosition}, 500, 'swing');
			}

			if (this.clickTarget.length) {
				this.clickTarget.click();
			}
		}
	}, {
		element: undefined,
		moveTarget: undefined,
		clickTarget: undefined
	});

})(jQuery);
