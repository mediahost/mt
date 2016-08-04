var ComponentsDropdowns = function () {

	var handleSelect2 = function () {

		var formatResult = function (result) {
			if (result.loading)
				return result.text;
			var markup = '<div class="clearfix">' +
					'<div class="col-sm-1">' +
						'<img src="' + result.image_thumbnail_100 + '" style="max-width: 100%" />' +
					'</div>' +
					'<div clas="col-sm-12">' +
						'<div class="clearfix">' +
							'<div class="col-sm-10">' + result.text + '</div>' +
							'<div class="col-sm-2"><b>' + result.priceWithVatFormated + '</b></div>' +
							'<div class="col-sm-2">' + result.inStore + ' ' + result.unit + '</div>' +
						'</div>' +
					'</div>';
			markup += '</div>';
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
			if ($(this).hasClass('autocompleteProducts') || $(this).hasClass('autocompleteStocks')) {
				var getProductId = $(this).hasClass('autocompleteProducts');
				params.ajax = {
					url: links['Product:searchJson'],
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							locale: lang,
							text: params.term,
							getProductId: getProductId ? 1 : 0,
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

	return {
		//main function to initiate the module
		init: function () {
			handleSelect2();
			handleMultiSelect();
		}
	};
}();