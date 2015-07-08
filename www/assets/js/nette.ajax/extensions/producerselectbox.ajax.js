/*!
 * @author Jakub Skrzeczek
 * @author Daniel Robenek
 * @license MIT
 */

(function ($, undefined) {

	$.nette.ext('producerSelectbox', {
		load: function () {
			this.hideSubmit();
			$('.' + this.controlClass).off('change', this.sendSelectBox).on('change', this.sendSelectBox);
		},
		success: function (payload) {
			for (var i in payload.snippets) {
				$('#' + i + ' form').each(function () {
					Nette.initForm(this);
				});
			}
		}
	}, {
		hideSubmit: function () {
			var ext = $.nette.ext('producerSelectbox');
			$('#' + ext.controlClass + ext.buttonSuffix).hide();
		},
		sendSelectBox: function () {
			var ext = $.nette.ext('producerSelectbox');
			$('#' + ext.controlClass + ext.buttonSuffix).click();
		},
		controlClass: 'dependentSelect',
		buttonSuffix: '_load'
	});

})(jQuery);
