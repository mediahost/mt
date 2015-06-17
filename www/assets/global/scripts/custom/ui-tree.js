var UITree = function () {

	var handleCategoryTree = function ()
	{
		var translates = {
			'edit' : {
				'en': 'Edit',
				'cs': 'Upravit',
				'sk': 'Upraviť'
			},
			'create_subcategory' : {
				'en': 'Create subcategory',
				'cs': 'Vytvořit podkategorii',
				'sk': 'Vytvoriť podkategóriu'
			},
			'rename' : {
				'en': 'Rename',
				'cs': 'Přejmenovat',
				'sk': 'Premenovať'
			},
			'remove' : {
				'en': 'Remove',
				'cs': 'Odstranit',
				'sk': 'Odstraniť'
			}
		};
		var treeID = '#category_tree';
		var jstree = $(treeID).jstree({
			"core": {
				'strings': {
					'New node': 'New category'
				},
				"themes": {
					"responsive": true,
					'dots': false
				},
				"check_callback": function (operation, node, node_parent, node_position, more) {
					// operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
					switch (operation) {
						case 'create_node':
						case 'rename_node':
						case 'delete_node':
							return true;
						case 'move_node':
						case 'copy_node':
						default:
							return false;
					}
				},
				'data': {
					'url': function (node) {
						return basePath + '/ajax/categories/get-subcategories';
					},
					'data': function (node) {
						return {'parent': node.id, 'lang': lang};
					},
					'dataType': 'jsonp'
				}
			},
			"types": {
				"loaded": {
					"icon": "fa fa-folder icon-lg icon-state-info"
				}
			},
			"contextmenu": {
				"items": function (node) {
					var instance = $(treeID).jstree(true);
					return {
						"Edit": {
							"label": translates.edit[lang],
							"action": function (obj) {
								window.location.href = node.a_attr.href;
							}
						},
						"Rename": {
							"label": translates.rename[lang],
							"action": function (obj) {
								instance.edit(node);
							}
						},
						"Create": {
							"label": translates.create_subcategory[lang],
							"action": function (obj) {
								var newNode = instance.create_node(node);
								instance.edit(newNode);
							}
						},
						"Remove": {
							"label": translates.remove[lang],
							"action": function (obj) {
								instance.delete_node(node);
							}
						}
					};
				}
			},
			"state": {"key": "categories"},
			"plugins": ["state", "contextmenu", "types"]
		});
		jstree.on('create_node.jstree', function (e, data) {
			var instance = data.instance;
			var node = data.node;
			var parent = data.parent;
			var request = $.get(
					basePath + '/ajax/categories/create-category',
					{name: node.text, parent: parent, lang: lang}
			).success(function (e) {
				if (e.success && e.success.id) {
					instance.set_id(node, e.success.id);
				} else if (e.error) {
					instance.delete_node(node);
					alert(e.error);
				}
			}).fail(function () {
				instance.delete_node(node);
			});
		});
		jstree.on('rename_node.jstree', function (e, data) {
			var instance = data.instance;
			var node = data.node;
			var request = $.get(
					basePath + '/ajax/categories/rename-category',
					{id: node.id, name: node.text, lang: lang}
			).success(function (e) {
				if (e.success && e.success.id) {
					instance.set_text(node, e.success.name);
				} else if (e.error) {
					instance.set_text(node, node.text);
					alert(e.error);
				}
			}).fail(function () {
				instance.set_text(node, node.text);
			});
		});
		jstree.on('delete_node.jstree', function (e, data) {
			var instance = data.instance;
			var node = data.node;
			if ($.isNumeric(node.id)) {
				var request = $.get(
						basePath + '/ajax/categories/delete-category',
						{id: node.id}
				).success(function (e) {
					if (e.success && e.success.id) {
						return true;
					} else if (e.error) {
						alert(e.error);
						instance.refresh();
					}
				}).fail(function () {
					instance.refresh();
				});
			}
		});

	};


	return {
		//main function to initiate the module
		init: function () {

			handleCategoryTree();

		}

	};

}();