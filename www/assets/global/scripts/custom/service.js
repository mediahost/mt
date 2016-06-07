var Service = function () {

	var initService = function () {
		var formSelector = '.modelSelector';
		var treeSelector = '#treeProducers';
		var selectorProducers = formSelector + ' select[name=producer]';
		var $selectorProducers = $(selectorProducers);
		var selectorLines = formSelector + ' select[name=line]';
		var $selectorLines = $(selectorLines);
		var selectorModels = formSelector + ' select[name=model]';
		var $selectorModels = $(selectorModels);

		var tree = jQuery.parseJSON($(treeSelector).attr('data-producers-tree'));

		$(document).on('change', selectorProducers, function () {
			var producerId = $selectorProducers.val();
			if (tree[producerId]) {
				var select = $selectorLines;
				var entity = tree[producerId];
				fillOptions(select, entity);
				realoadSelect2(select);
				realoadSelect2($selectorLines);
				realoadSelect2($selectorModels);
			} else {
				$selectorLines.attr('disabled', true);
				$selectorModels.attr('disabled', true);
			}
		});

		$(document).on('change', selectorLines, function () {
			var producerId = $selectorProducers.val();
			var lineId = $selectorLines.val();
			if (tree[producerId] && tree[producerId]['children'] && tree[producerId]['children'][lineId]) {
				var entity = tree[producerId]['children'][lineId];
				fillOptions($selectorModels, entity);
				realoadSelect2($selectorModels);
			} else {
				$selectorModels.attr('disabled', true);
			}
		});

		$(document).on('change', formSelector + ' select.sendFormOnChange', function () {
			var modelId = $selectorModels.val();
			var modelParam = '?id=' + modelId;
			var action = $(formSelector).attr('action')
			action = action.split('?')[0];

			$('#frm-requestForm-form').attr('action', action + modelParam);
			$(formSelector).attr('action', action + modelParam);
			// history.pushState('', '', modelParam);
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

			var elements = [];
			var entityCount = 0;
			for (var id in entity['children']) {
				entityCount++;
				var priority = entity['children'][id]['priority'];
				elements[priority] = {
					'id': id,
					'name': entity['children'][id]['name']
				};
			}

			var elementCount = 0;
			for (var i in elements) {
				elementCount++;
			}

			if (elementCount != entityCount) {
				elements = [];
				for (var id in entity['children']) {
					elements[id] = {
						'id': id,
						'name': entity['children'][id]['name']
					};
				}
			}

			for (var i in elements) {
				item = $('<option></option>')
					.attr('value', elements[i].id)
					.html(elements[i].name);
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