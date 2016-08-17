var Frontend = function () {

	var assetsPath = basePath + '/assets/frontend/';

	var imgPath = 'img/';

    var overlayColor = '#fff';

    var overlayOpacity = 0.7;

    var overlayOpacityBoxed = 0.5;

	var handleOwl = function () {
		$('.owl-carousel').each(function(){
			$(this).owlCarousel();
		});
	};

	var handleLightbox = function () {
		$('#popup-gallery').each(function() {
			$(this).magnificPopup({
				delegate: 'a.popup-gallery-image',
				type: 'image',
				gallery: {
					enabled: true
				}
			});
		});
	};

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
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '">'
				+ '<div class="block-spinner-bar">'
				+ '<div class="bounce1"></div>'
				+ '<div class="bounce2"></div>'
				+ '<div class="bounce3"></div>'
				+ '</div></div>';
		} else if (options.iconOnly) {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '">'
				+ '<img src="' + Frontend.getImgPath() + 'loading-spinner.gif" align="">'
				+ '</div>';
		} else if (options.textOnly) {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '">'
				+ '<span>&nbsp;&nbsp;' + (options.message ? options.message : 'LOADING...') + '</span>'
				+ '</div>';
		} else {
			html = '<div class="loading-message ' + (options.boxed ? 'loading-message-boxed' : '') + '">'
				+ '<img src="' + Frontend.getImgPath() + 'loading-spinner.gif" align="">'
				+ '<span>&nbsp;&nbsp;' + (options.message ? options.message : 'LOADING...') + '</span>'
				+ '</div>';
		}

		if (options.target) { // element blocking
			var el = $(options.target);
            var htmlHeight = 80;
            var htmlTop = '10%';
            var elBottom = el.position().top + el.height();
			if (el.height() <= ($(window).height())) {
				options.cenrerY = true;
			} else if ($(window).scrollTop() > el.position().top) {
                var screenHeight = $(window).height();
                var restHeight = elBottom - $(window).scrollTop();
                var heightToSplit = restHeight < screenHeight ? restHeight : screenHeight;
			    var sizeYMiddle = heightToSplit / 2;
                if (sizeYMiddle > htmlHeight) {
                    var elScrollTop = $(window).scrollTop() - el.position().top;
                    htmlTop = (elScrollTop + sizeYMiddle - (htmlHeight / 2)) + 'px';
                }
            }
			el.block({
				message: html,
				baseZ: options.zIndex ? options.zIndex : 1000,
				centerY: options.cenrerY !== undefined ? options.cenrerY : false,
				css: {
					top: htmlTop,
					border: '0',
					padding: '0',
					backgroundColor: 'none'
				},
				overlayCSS: {
					backgroundColor: options.overlayColor ? options.overlayColor : overlayColor,
					opacity: options.boxed ? overlayOpacityBoxed : overlayOpacity,
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
					backgroundColor: options.overlayColor ? options.overlayColor : overlayColor,
					opacity: options.boxed ? overlayOpacityBoxed : overlayOpacity,
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

	var handleDisableButtons = function () {
		$(document).on('click', '.btn.disabled', function (e) {
			e.preventDefault();
		});
	};

	var handleSelect2 = function () {

		$('select.select2').each(function () {
			$(this).prop('disabled', this.hasAttribute('data-disabled') ? 'disabled' : false);
			var params = {};
			for (var i = 0, attrs = this.attributes, l = attrs.length; i < l; i++) {
				var attr = attrs.item(i).nodeName;
				if (attr === "placeholder" || attr === "data-placeholder") {
					params.placeholder = $(this).attr(attr);
					params.allowClear = true;
				} else if (attr === "data-tags") {
					params.tags = JSON.parse($(this).attr(attr));
				} else if (attr.substring(0, 5) === "data-") {
					var paramName = attr.substring(5).replace(/\-/g, "_");
					var paramNameIndex = paramName.indexOf("_");
					while (paramNameIndex !== -1) {
						paramName = paramName.substring(0, paramNameIndex)
							+ paramName.substring(paramNameIndex + 1, paramNameIndex + 2).toUpperCase()
							+ paramName.substring(paramNameIndex + 2);
						paramNameIndex = paramName.indexOf("_");
					}
					params[paramName] = $(this).attr(attr);
				}
			}
			$(this).select2(params);
		});

	};

	var handleSearch = function () {

		if ($.fn.typeahead === undefined) {
			console.error('Plugin "typeahead.js" is missing! Run `bower install typeahead.js` and load bundled version.');
			return;
		} else if (window.Bloodhound === undefined) {
			console.error('Plugin "Bloodhound" required by "typeahead.js" is missing!');
			return;
		}

		var locale = {
			'empty': {
				'en': 'Searched term does not match any product',
				'cs': 'Hledanému výrazu neodpovídá žádný produkt',
				'sk': 'Hľadanému výrazu neodpovedá žiadny produkt'
			},
			'all': {
				'en': 'Show all',
				'cs': 'Zobrazit vše',
				'sk': 'Zobraziť všetko'
			}
		};

		var url = links['Category:searchJson'];
		var wildcard = '-QUERY-';
		var params = {
			'text': wildcard,
			'currency': currencyName,
			'locale': lang
		};

		var urlParams = function (url, params) {
			var glue = '?';
			if (url && url.match(/\?/)) {
				glue = '&';
			}
			return url + glue + $.param(params);
		};

		var moreLink;
		var transformFinded = function (response) {
			moreLink = response.more;
			return response.items;
		};

		var options = {
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			remote: {
				url: urlParams(url, params),
				wildcard: wildcard,
				transform: transformFinded
			}
		};

		if (window.NProgress !== undefined) {
			options.remote.ajax = {
				beforeSend: $.proxy(window.NProgress.start),
				complete: $.proxy(window.NProgress.done)
			};
		}

		var source = new Bloodhound(options);

		var formatResult = function (result) {
			if (result.loading)
				return result.text;
			var markup = [
				'<div class="clearfix">',
				'<a href="' + result.url + '">',
				'<div class="col-sm-1 image">',
				'<img src="' + result.image_thumbnail_100 + '" style="max-width: 100%" />',
				'</div>',
				'<div class="col-sm-8 text">' + result.shortText + '</div>',
				'<div class="col-sm-3 price"><strong>' + result.priceWithVatFormated + '</strong></div>',
				'</a>',
				'</div>'
			].join('\n');
			return markup;
		};

		$('#frm-search .search-input').typeahead(null, {
			name: 'search',
			display: 'text',
			source: source,
			limit: 10,
			templates: {
				empty: '<div class="empty-message">' + locale.empty[lang] + '</div>',
				suggestion: formatResult,
				footer: '<div class="more-message"><a href="' + moreLink + '">' + locale.all[lang] + '</a></div>',
			}
		});
	};

	return {
		init: function () {
			handleOwl();
			handleLightbox();
			handleICheck();
			handleProductFilter();
			handleProductDetail();
			handleDisableButtons();
			handleSelect2();
			handleSearch();
		},
		afterAjaxInit: function () {
			handleInitICheck();
			handleProductFilter();
			handleSelect2();
		},
		blockUI: function (options) {
			handleBlockUi(options);
		},
		unblockUI: function (target) {
			handleUnblockUi(target);
		},
		getAssetsPath: function () {
			return assetsPath;
		},
		getImgPath: function () {
			return assetsPath + imgPath;
		}
	};

}();
