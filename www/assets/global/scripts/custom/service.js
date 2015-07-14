var Service = function () {

	var initService = function () {
		var formSelector = '.modelSelector';
		var treeSelector = '#treeProducers';
		var selectorProducers = formSelector + ' select[name=producer]';
		var selectorLines = formSelector + ' select[name=line]';
		var selectorModels = formSelector + ' select[name=model]';

		var tree = jQuery.parseJSON($(treeSelector).attr('data-producers-tree'));

		$(document).on('change', selectorProducers, function () {
			var producerId = $(selectorProducers).val();
			if (tree[producerId]) {
				var select = $(selectorLines);
				var entity = tree[producerId];
				fillOptions(select, entity);
				realoadSelect2(select);
				realoadSelect2($(selectorLines));
				realoadSelect2($(selectorModels));
			} else {
				$(selectorLines).attr('disabled', true);
				$(selectorModels).attr('disabled', true);
			}
		});

		$(document).on('change', selectorLines, function () {
			var producerId = $(selectorProducers).val();
			var lineId = $(selectorLines).val();
			if (tree[producerId] && tree[producerId]['children'] && tree[producerId]['children'][lineId]) {
				var select = $(selectorModels);
				var entity = tree[producerId]['children'][lineId];
				fillOptions(select, entity);
				realoadSelect2($(selectorModels));
			} else {
				$(selectorModels).attr('disabled', true);
			}
		});

		$(document).on('change', formSelector + ' select.sendFormOnChange', function () {
			$(this).parents('form').submit();
		});

	};

	var realoadSelect2 = function (select) {
		select.select2({
			allowClear: true
		});
	};

	var fillOptions = function (select, entity) {
		select.attr('disabled', false);
		if (entity['children']) {
			select.find("option").remove();

			var item = $('<option></option>').attr('value', '');
			select.prepend(item);

			for (var i in entity['children']) {
				var name = entity['children'][i]['name'];
				item = $('<option></option>').attr('value', i).html(name);
				select.append(item);
			}
		}
	};

	return {
		init: function () {
			initService();
		}
	};

}();