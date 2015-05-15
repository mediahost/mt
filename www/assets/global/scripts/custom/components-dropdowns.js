var ComponentsDropdowns = function () {

	var handleSelect2 = function () {

		$("select.select2").each(function () {
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
