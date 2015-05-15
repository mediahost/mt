var TableManaged = function () {

	var initTables = function () {

		var table = $('.datatable');

		// begin first table
		table.dataTable({
			"lengthMenu": [
				[5, 15, 20, 50, 100, -1],
				[5, 15, 20, 50, 100, "All"] // change per page values here
			],
			// set the initial value
			"pageLength": 10,
			"pagingType": "bootstrap_full_number",
			"language": {
				"lengthMenu": "_MENU_ records",
				"paginate": {
					"previous": "Prev",
					"next": "Next",
					"last": "Last",
					"first": "First"
				}
			},
			"columnDefs": [{// set default column settings
					'orderable': true,
					'targets': [0]
				}, {
					"searchable": true,
					"targets": [0]
				}],
			"order": [
				[1, "asc"]
			] // set first column as a default sort by asc
		});

		var tableWrapper = jQuery('.dataTables_wrapper');

		tableWrapper.find('.dataTables_length select').select2({
			showSearchInput: false //hide search box with special css class
		}); // initialize select2 dropdown

		$(".delete-confirm").click(function () {
			if (!confirm('Are you sure?')) {
				return false;
			}
		});
	};

	return {
		//main function to initiate the module
		init: function () {
			if (!jQuery().dataTable) {
				return;
			}

			initTables();
		}

	};

}();
