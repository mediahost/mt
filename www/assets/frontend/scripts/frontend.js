var Frontend = function () {

	var handleInitICheck = function () {
		$('.i-check, .i-radio').iCheck({
			checkboxClass: 'i-check',
			radioClass: 'i-radio'
		});
	};

	var handleICheck = function () {
		handleInitICheck();
		$(document).on('ifChanged', 'input.i-check', function (e) {
			$(this).change();
		});
		$(document).on('ifChanged', 'input.i-radio', function (e) {
			if ($(e.target).is(':checked')) {
				$(this).change();
			}
		});
	};

	var handleProductDetail = function () {
		$("#homecreditCalc").fancybox({
			'height': '90%'
		});
	};

	var handleProductFilter = function () {
		$("#frm-products-filterForm-price")
			.ionRangeSlider({
				onFinish: function (data) {
					$("#frm-products-filterForm-price").closest('form.sendOnChange').submit();
				}
			});
	};

	var handleBlockUi = function (options) {
		options = $.extend(true, {}, options);
		var html = '';
		if (options.animate) {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '">' + '<div class="block-spinner-bar"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>' + '</div>';
		} else if (options.iconOnly) {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '"><img src="' + this.getGlobalImgPath() + 'loading-spinner-grey.gif" align=""></div>';
		} else if (options.textOnly) {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '"><span>&nbsp;&nbsp;' + (options.message ? options.message : 'LOADING...') + '</span></div>';
		} else {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '"><img src="' + this.getGlobalImgPath() + 'loading-spinner-grey.gif" align=""><span>&nbsp;&nbsp;' + (options.message ? options.message : 'LOADING...') + '</span></div>';
		}

		if (options.target) { // element blocking
			var el = $(options.target);
			if (el.height() <= ($(window).height())) {
				options.cenrerY = true;
			}
			el.block({
				message: html,
				baseZ: options.zIndex ? options.zIndex : 1000,
				centerY: options.cenrerY !== undefined ? options.cenrerY : false,
				css: {
					top: '10%',
					border: '0',
					padding: '0',
					backgroundColor: 'none'
				},
				overlayCSS: {
					backgroundColor: options.overlayColor ? options.overlayColor : '#555',
					opacity: options.boxed ? 0.05 : 0.1,
					cursor: 'wait'
				}
			});
		} else { // page blocking
			$.blockUI({
				message: html,
				baseZ: options.zIndex ? options.zIndex : 1000,
				css: {
					border: '0',
					padding: '0',
					backgroundColor: 'none'
				},
				overlayCSS: {
					backgroundColor: options.overlayColor ? options.overlayColor : '#555',
					opacity: options.boxed ? 0.05 : 0.1,
					cursor: 'wait'
				}
			});
		}
	}

	var handleUnblockUi = function (target) {
		if (target) {
			$(target).unblock({
				onUnblock: function () {
					$(target).css('position', '');
					$(target).css('zoom', '');
				}
			});
		} else {
			$.unblockUI();
		}
	};

	return {
		init: function () {
			handleICheck();
			handleProductFilter();
			handleProductDetail();
		},
		afterAjaxInit: function () {
			handleInitICheck();
		},
		blockUI: function (options) {
			handleBlockUi(options);
		},
		unblockUI: function (target) {
			handleUnblockUi(target);
		}
	};

}();
