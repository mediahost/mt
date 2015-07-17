var ComponentsDropdowns = function () {

	var handleSelect2 = function () {

		var formatResult = function (result) {
			if (result.loading)
				return result.text;
			var markup = '<div class="clearfix">' +
					'<div class="col-sm-1">' +
					'<img src="' + result.image_thumbnail_100 + '" style="max-width: 100%" />' +
					'</div>' +
					'<div clas="col-sm-10">' +
					'<div class="clearfix">' +
					'<div class="col-sm-10">' + result.text + '</div>' +
					'</div>';
			markup += '</div></div>';
			return markup;
		};

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
			if ($(this).hasClass('autocompleteProducts')) {
				params.ajax = {
					url: basePath + '/ajax/products/find-by-name',
					dataType: 'jsonp',
					delay: 250,
					data: function (params) {
						return {
							locale: lang,
							text: params.term,
							page: params.page,
							perPage: 30
						};
					},
					processResults: function (data, params) {
						params.page = params.page || 1;
						return {
							results: data.items,
							pagination: {
								more: (params.page * 30) < data.total_count
							}
						};
					},
					cache: true
				};
				params.minimumInputLength = 1;
				params.escapeMarkup = function (m) {
					return m;
				};
				params.templateResult = formatResult;
				params.templateSelection = function (item) {
					return item.text;
				};
			}
			$(this).select2(params);
		});
	};

	var handleMultiSelect = function () {
		$('select.multi-select').multiSelect({
			selectableOptgroup: true
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
			}
		};

		var transformFinded = function (response) {
			return response.items;
		};

		var options = {
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			remote: {
				url: basePath + '/ajax/stocks/find-by-fulltext?text=%QUERY&currency=' + currencyName + '&locale=' + lang,
				wildcard: '%QUERY',
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
					'<div class="col-sm-2 image">',
						'<img src="' + result.image_thumbnail_100 + '" style="max-width: 100%" />',
					'</div>',
					'<div class="col-sm-7 text">' + result.shortText + '</div>',
					'<div class="col-sm-3 price"><strong>' + result.priceWithVatFormated + '</strong></div>',
				'</a>',
				'</div>'
			].join('\n');
			return markup;
		};

		$('#frm-search .typeahead').typeahead(null, {
			name: 'search',
			display: 'text',
			source: source,
			limit: 10,
			templates: {
				empty: '<div class="empty-message">' + locale.empty[lang] + '</div>',
				suggestion: formatResult
			}
		});
	};
	return {
		//main function to initiate the module
		init: function () {
			handleSelect2();
			handleMultiSelect();
			handleSearch();
		}
	};
}();