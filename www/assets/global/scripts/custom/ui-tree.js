var UITree = function () {

	var handleCategoryTree = function ()
	{
		var stateKey = 'categories';
		var treeBlockId = '#categories-tree';
		var editBlockId = '#snippet--categoryPortlet';
		var treeBlock = $(treeBlockId);
		var selectedId = parseInt(treeBlock.attr('data-selected-id'));
		if (selectedId > 0) {
			var vakataStorage = $.vakata.storage.get(stateKey);
			if (!!vakataStorage) {
				try {
					var stateSettings = JSON.parse(vakataStorage);
					if (stateSettings.state && stateSettings.state.core) {
						stateSettings.state.core.selected = [selectedId];
						$.vakata.storage.set(stateKey, JSON.stringify(stateSettings));
					}
				}
				catch (ex) {
				}
			}
		}

		var locale = {
			'new_node': {
				'en': 'New category',
				'cs': 'Nová kategorie',
				'sk': 'Nová kategoria'
			},
			'create_subcategory': {
				'en': 'Create subcategory',
				'cs': 'Vytvořit podkategorii',
				'sk': 'Vytvoriť podkategóriu'
			},
			'rename': {
				'en': 'Rename',
				'cs': 'Přejmenovat',
				'sk': 'Premenovať'
			},
			'remove': {
				'en': 'Remove',
				'cs': 'Odstranit',
				'sk': 'Odstraniť'
			}
		};
		var treeID = '#category_tree';
		var jstree = $(treeID).jstree({
			'core': {
				'strings': {
					'New node': locale.new_node[lang]
				},
				'themes': {
					'responsive': true,
					'dots': false
				},
				'check_callback': function (operation, node, node_parent, node_position, more) {
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
			'types': {
				'loaded': {
					'icon': 'fa fa-folder icon-lg icon-state-info'
				}
			},
			'contextmenu': {
				'items': function (node) {
					var instance = $(treeID).jstree(true);
					return {
						'Rename': {
							'label': locale.rename[lang],
							'action': function (obj) {
								instance.edit(node);
							}
						},
						'Create': {
							'label': locale.create_subcategory[lang],
							'action': function (obj) {
								var newNode = instance.create_node(node);
								instance.edit(newNode);
							}
						},
						'Remove': {
							'label': locale.remove[lang],
							'action': function (obj) {
								instance.delete_node(node);
							}
						}
					};
				}
			},
			'state': {'key': stateKey},
			'plugins': ['state', 'contextmenu', 'types']
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
		jstree.on('changed.jstree', function (e, data) {
			if (data.action === 'select_node' && data.event) {
				Metronic.blockUI({
					target: $(treeBlockId),
					animate: true
				});
				Metronic.blockUI({
					target: $(editBlockId),
					animate: true
				});
				$.post(basePath + '/app/categories/default/' + data.node.id)
						.done(function (payload) {
							if (payload.redirect) {
								window.location.href = payload.redirect;
							}
							if (payload.snippets) {
								var snippetsExt = $.nette.ext('snippets');
								snippetsExt.updateSnippets(payload.snippets);
							}
							Metronic.unblockUI($(treeBlockId));
							Metronic.unblockUI($(editBlockId));
						});
			}
		});

	};

	var handleProducerTree = function ()
	{
		var stateKey = 'producers';
		var treeBlockId = '#producers-tree';
		var editBlockId = '#snippet--producerPortlet';
		var treeBlock = $(treeBlockId);
		var selectedId = treeBlock.attr('data-selected-id');
		if (selectedId) {
			var vakataStorage = $.vakata.storage.get(stateKey);
			if (!!vakataStorage) {
				try {
					var stateSettings = JSON.parse(vakataStorage);
					if (stateSettings.state && stateSettings.state.core) {
						stateSettings.state.core.selected = [selectedId];
						$.vakata.storage.set(stateKey, JSON.stringify(stateSettings));
					}
				}
				catch (ex) {
				}
			}
		}

		var locale = {
			'new_node_producer': {
				'en': 'New producer',
				'cs': 'Nový výrobce',
				'sk': 'Nový výrobca'
			},
			'new_node_line': {
				'en': 'New line',
				'cs': 'Nová řada',
				'sk': 'Nová rada'
			},
			'new_node_model': {
				'en': 'New model',
				'cs': 'Nový model',
				'sk': 'Nový model'
			},
			'create_line': {
				'en': 'Create line',
				'cs': 'Vytvořit řadu',
				'sk': 'Vytvoriť radu'
			},
			'create_model': {
				'en': 'Create model',
				'cs': 'Vytvořit model',
				'sk': 'Vytvoriť model'
			},
			'rename': {
				'en': 'Rename',
				'cs': 'Přejmenovat',
				'sk': 'Premenovať'
			},
			'remove': {
				'en': 'Remove',
				'cs': 'Odstranit',
				'sk': 'Odstraniť'
			}
		};
		var treeID = '#producer_tree';
		var jstree = $(treeID).jstree({
			'core': {
				'strings': {
					'New node': locale.new_node_producer[lang]
				},
				'themes': {
					'responsive': true,
					'dots': false
				},
				'check_callback': function (operation, node, node_parent, node_position, more) {
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
						return basePath + '/ajax/producers/get-producers';
					},
					'data': function (node) {
						return {'parent': node.id, 'lang': lang};
					},
					'dataType': 'jsonp'
				}
			},
			'types': {
				'producer': {
					'icon': 'fa fa-building-o icon-lg icon-state-info'
				},
				'line': {
					'icon': 'fa fa-cubes icon-lg icon-state-success'
				},
				'model': {
					'icon': 'fa fa-paperclip icon-lg'
				}
			},
			'contextmenu': {
				'items': function (node) {
					var instance = $(treeID).jstree(true);
					var ops = {};
					ops.rename = {
						'label': locale.rename[lang],
						'action': function (obj) {
							instance.edit(node);
						}
					};
					var createName = locale.create_line[lang];
					var allowedCreate = true;
					switch (node.type) {
						case 'producer':
							createName = locale.create_line[lang];
							break
						case 'line':
							createName = locale.create_model[lang];
							break;
						default:
							allowedCreate = false;
							break;
					}
					if (allowedCreate) {
						ops.create = {
							'label': createName,
							'action': function (obj) {
								var nodeAttrs = {};
								switch (node.type) {
									case 'producer':
										nodeAttrs.text = locale.new_node_line[lang];
										nodeAttrs.type = 'line';
										break
									case 'line':
										nodeAttrs.text = locale.new_node_model[lang];
										nodeAttrs.type = 'model';
										break;
								}
								var newNode = instance.create_node(node, nodeAttrs);
								instance.edit(newNode);
							}
						};
					}
					ops.remove = {
						'label': locale.remove[lang],
						'action': function (obj) {
							instance.delete_node(node);
						}
					};
					return ops;
				}
			},
			'state': {'key': stateKey},
			'plugins': ['state', 'contextmenu', 'types']
		});
		jstree.on('create_node.jstree', function (e, data) {
			var instance = data.instance;
			var node = data.node;
			var parent = data.parent;
			var request = $.get(
					basePath + '/ajax/producers/create-producer',
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
					basePath + '/ajax/producers/rename-producer',
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
			var request = $.get(
					basePath + '/ajax/producers/delete-producer',
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
		});
		jstree.on('changed.jstree', function (e, data) {
			if (data.action === 'select_node' && data.event) {
				Metronic.blockUI({
					target: $(treeBlockId),
					animate: true
				});
				Metronic.blockUI({
					target: $(editBlockId),
					animate: true
				});
				$.post(basePath + '/app/producers/default/' + data.node.id)
						.done(function (payload) {
							if (payload.redirect) {
								window.location.href = payload.redirect;
							}
							if (payload.snippets) {
								var snippetsExt = $.nette.ext('snippets');
								snippetsExt.updateSnippets(payload.snippets);
							}
							Metronic.unblockUI($(treeBlockId));
							Metronic.unblockUI($(editBlockId));
							GlobalCustomInit.onChangeJSTree();
						});
			}
		});

	};

	return {
		//main function to initiate the module
		init: function () {

			handleCategoryTree();
			handleProducerTree();

		}

	};

}();