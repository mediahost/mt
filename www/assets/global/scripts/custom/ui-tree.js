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
							if (more && more.dnd) {
								return more.pos !== "i" && node_parent.id == node.parent;
							}
							return true;
						case 'copy_node':
						default:
							return false;
					}
				},
				'data': {
					'url': function (node) {
						return links['Categories:getSubcategories'];
					},
					'data': function (node) {
						return {'parent': node.id};
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
			'dnd': {
				'drop_target': false,
				'drag_target': false
			},
			'state': {'key': stateKey},
			'plugins': ['state', 'contextmenu', 'types', 'checkbox', 'dnd']
		});
		jstree.on('create_node.jstree', function (e, data) {
			var instance = data.instance;
			var node = data.node;
			var parent = data.parent;
			var request = $.get(
					links['Categories:createCategory'],
					{name: node.text, parent: parent}
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
					links['Categories:renameCategory'],
					{id: node.id, name: node.text}
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
						links['Categories:deleteCategory'],
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
		jstree.on('move_node.jstree', function (e, data) {
			var node = data.node;
			var request = $.get(
				links['Categories:reorderCategory'],
				{id: node.id, old: data.old_position, new: data.position}
			);
		});
        jstree.on('changed.jstree', function (e, data) {
            if (data.event) {
                var checked = $(treeID).jstree('get_checked', null, true);
                var params = '?' + $.param({categoryIds: checked});
                var url = links['Products:exportCategory'] + params;
                $('#categories-export').attr('href', url);
            }
            if (data.action === 'select_node' && data.event && $(data.event.target).is('a')) {
                Metronic.blockUI({
                    target: $(treeBlockId),
                    animate: true
                });
                Metronic.blockUI({
                    target: $(editBlockId),
                    animate: true
                });
                $.post(links['Categories:default'], {categoryId: data.node.id})
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
        jstree.on('ready.jstree', function (e, data) {
            var checked = $(treeID).jstree('get_checked', null, true);
            var params = '?' + $.param({categoryIds: checked});
            var url = links['Products:exportCategory'] + params;
            $('#categories-export').attr('href', url);
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
							if (more && more.dnd) {
								return more.pos !== "i" && node_parent.id == node.parent;
							}
							return true;
						case 'copy_node':
						default:
							return false;
					}
				},
				'data': {
					'url': function (node) {
						return links['Producers:getProducers'];
					},
					'data': function (node) {
						return {'parent': node.id};
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
			'dnd': {
				'drop_target': false,
				'drag_target': false
			},
			'state': {'key': stateKey},
			'plugins': ['state', 'contextmenu', 'types', 'dnd']
		});
		jstree.on('create_node.jstree', function (e, data) {
			var instance = data.instance;
			var node = data.node;
			var parent = data.parent;
			var request = $.get(
					links['Producers:createProducer'],
					{name: node.text, parent: parent}
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
					links['Producers:renameProducer'],
					{id: node.id, name: node.text}
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
					links['Producers:deleteProducer'],
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
		jstree.on('move_node.jstree', function (e, data) {
			var node = data.node;
			var request = $.get(
				links['Producers:reorderProducer'],
				{id: node.id, old: data.old_position, new: data.position}
			);
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
				$.post(links['Producers:default'], {producerId: data.node.id})
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

	var handleProducerQuestionTree = function ()
	{
		var stateKey = 'producersQuestion';
		var treeBlockId = '#producers-question-tree';
		var editBlockId = '#snippet--modelPortlet';
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
		var treeID = '#producer_question_tree';
		var jstree = $(treeID).jstree({
			'core': {
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
						case 'move_node':
						case 'copy_node':
						default:
							return false;
					}
				},
				'data': {
					'url': function (node) {
						return links['Producers:getProducers'];
					},
					'data': function (node) {
						return {'parent': node.id};
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
					'icon': 'fa fa-cube icon-lg'
				}
			},
			'state': {'key': stateKey},
			'plugins': ['state', 'types']
		});
		jstree.on('changed.jstree', function (e, data) {
			if (data.action === 'select_node' && data.event && data.node.id.charAt(0) === 'm') {
				Metronic.blockUI({
					target: $(editBlockId),
					animate: true
				});

				$.nette.ajax({
					url: links['Buyout:default'],
					data: 'modelId=' + data.node.id.substring(2),
					beforeSend: function () {

					},
					success: function (payload, status, xhr) {
						Metronic.unblockUI($(editBlockId));
						GlobalCustomInit.onChangeJSTree();
					},
					complete: function (jqXHR, textStatus) {

					}
				});
			}
		});

	};

	return {
		//main function to initiate the module
		init: function () {

			handleCategoryTree();
			handleProducerTree();
			handleProducerQuestionTree();

		}

	};

}();