var ComponentsDropdowns = function () {

	var handleSelect2 = function () {

		$('select.select2').each(function () {
			var params = {};
			for (var i = 0, attrs = this.attributes, l = attrs.length; i < l; i++) {
				var attr = attrs.item(i).nodeName;
				if (attr === "placeholder") {
					params.placeholder = $(this).attr(attr);
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
					url: basePath + "/ajax/products/find-by-name",
					dataType: 'jsonp',
					delay: 250,
					data: function (params) {
						return {
							lang: lang,
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
				params.escapeMarkup = function (markup) {
					return markup;
				};
				params.templateResult = formatRepo;
				params.templateSelection = function (repo) {
					return repo.text;
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


function formatRepo(repo) {
	if (repo.loading)
		return repo.text;

	var markup = '<div class="clearfix">' +
			'<div class="col-sm-1">' +
			'<img src="' + repo.image_thumbnail_100 + '" style="max-width: 100%" />' +
			'</div>' +
			'<div clas="col-sm-10">' +
			'<div class="clearfix">' +
			'<div class="col-sm-10">' + repo.text + '</div>' +
			'</div>';

	markup += '</div></div>';

	return markup;
}