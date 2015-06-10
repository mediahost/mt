var ComponentsFormTools = function () {

	var handleTwitterTypeahead = function () {

		// Example #1
		// instantiate the bloodhound suggestion engine
		var numbers = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.num);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			local: [
				{num: 'metronic'},
				{num: 'keenthemes'},
				{num: 'metronic theme'},
				{num: 'metronic template'},
				{num: 'keenthemes team'}
			]
		});

		// initialize the bloodhound suggestion engine
		numbers.initialize();

		// instantiate the typeahead UI
		if (Metronic.isRTL()) {
			$('#typeahead_example_1').attr("dir", "rtl");
		}
		$('#typeahead_example_1').typeahead(null, {
			displayKey: 'num',
			hint: (Metronic.isRTL() ? false : true),
			source: numbers.ttAdapter()
		});

		// Example #2
		var countries = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.name);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			limit: 10,
			prefetch: {
				url: 'demo/typeahead_countries.json',
				filter: function (list) {
					return $.map(list, function (country) {
						return {name: country};
					});
				}
			}
		});

		countries.initialize();

		if (Metronic.isRTL()) {
			$('#typeahead_example_2').attr("dir", "rtl");
		}
		$('#typeahead_example_2').typeahead(null, {
			name: 'typeahead_example_2',
			displayKey: 'name',
			hint: (Metronic.isRTL() ? false : true),
			source: countries.ttAdapter()
		});

		// Example #3
		var custom = new Bloodhound({
			datumTokenizer: function (d) {
				return d.tokens;
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			remote: 'demo/typeahead_custom.php?query=%QUERY'
		});

		custom.initialize();

		if (Metronic.isRTL()) {
			$('#typeahead_example_3').attr("dir", "rtl");
		}
		$('#typeahead_example_3').typeahead(null, {
			name: 'datypeahead_example_3',
			displayKey: 'name',
			source: custom.ttAdapter(),
			hint: (Metronic.isRTL() ? false : true),
			templates: {
				suggestion: Handlebars.compile([
					'<div class="media">',
					'<div class="pull-left">',
					'<div class="media-object">',
					'<img src="{{img}}" width="50" height="50"/>',
					'</div>',
					'</div>',
					'<div class="media-body">',
					'<h4 class="media-heading">{{value}}</h4>',
					'<p>{{desc}}</p>',
					'</div>',
					'</div>',
				].join(''))
			}
		});

		// Example #4

		var nba = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.team);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			prefetch: 'demo/typeahead_nba.json'
		});

		var nhl = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.team);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			prefetch: 'demo/typeahead_nhl.json'
		});

		nba.initialize();
		nhl.initialize();

		if (Metronic.isRTL()) {
			$('#typeahead_example_4').attr("dir", "rtl");
		}
		$('#typeahead_example_4').typeahead({
			hint: (Metronic.isRTL() ? false : true),
			highlight: true
		},
		{
			name: 'nba',
			displayKey: 'team',
			source: nba.ttAdapter(),
			templates: {
				header: '<h3>NBA Teams</h3>'
			}
		},
		{
			name: 'nhl',
			displayKey: 'team',
			source: nhl.ttAdapter(),
			templates: {
				header: '<h3>NHL Teams</h3>'
			}
		});

	};

	var handleTwitterTypeaheadModal = function () {

		// Example #1
		// instantiate the bloodhound suggestion engine
		var numbers = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.num);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			local: [
				{num: 'metronic'},
				{num: 'keenthemes'},
				{num: 'metronic theme'},
				{num: 'metronic template'},
				{num: 'keenthemes team'}
			]
		});

		// initialize the bloodhound suggestion engine
		numbers.initialize();

		// instantiate the typeahead UI
		if (Metronic.isRTL()) {
			$('#typeahead_example_modal_1').attr("dir", "rtl");
		}
		$('#typeahead_example_modal_1').typeahead(null, {
			displayKey: 'num',
			hint: (Metronic.isRTL() ? false : true),
			source: numbers.ttAdapter()
		});

		// Example #2
		var countries = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.name);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			limit: 10,
			prefetch: {
				url: 'demo/typeahead_countries.json',
				filter: function (list) {
					return $.map(list, function (country) {
						return {name: country};
					});
				}
			}
		});

		countries.initialize();

		if (Metronic.isRTL()) {
			$('#typeahead_example_modal_2').attr("dir", "rtl");
		}
		$('#typeahead_example_modal_2').typeahead(null, {
			name: 'typeahead_example_modal_2',
			displayKey: 'name',
			hint: (Metronic.isRTL() ? false : true),
			source: countries.ttAdapter()
		});

		// Example #3
		var custom = new Bloodhound({
			datumTokenizer: function (d) {
				return d.tokens;
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			remote: 'demo/typeahead_custom.php?query=%QUERY'
		});

		custom.initialize();

		if (Metronic.isRTL()) {
			$('#typeahead_example_modal_3').attr("dir", "rtl");
		}
		$('#typeahead_example_modal_3').typeahead(null, {
			name: 'datypeahead_example_modal_3',
			displayKey: 'name',
			hint: (Metronic.isRTL() ? false : true),
			source: custom.ttAdapter(),
			templates: {
				suggestion: Handlebars.compile([
					'<div class="media">',
					'<div class="pull-left">',
					'<div class="media-object">',
					'<img src="{{img}}" width="50" height="50"/>',
					'</div>',
					'</div>',
					'<div class="media-body">',
					'<h4 class="media-heading">{{value}}</h4>',
					'<p>{{desc}}</p>',
					'</div>',
					'</div>',
				].join(''))
			}
		});

		// Example #4

		var nba = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.team);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			limit: 3,
			prefetch: 'demo/typeahead_nba.json'
		});

		var nhl = new Bloodhound({
			datumTokenizer: function (d) {
				return Bloodhound.tokenizers.whitespace(d.team);
			},
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			limit: 3,
			prefetch: 'demo/typeahead_nhl.json'
		});

		nba.initialize();
		nhl.initialize();

		$('#typeahead_example_modal_4').typeahead({
			hint: (Metronic.isRTL() ? false : true),
			highlight: true
		},
		{
			name: 'nba',
			displayKey: 'team',
			source: nba.ttAdapter(),
			templates: {
				header: '<h3>NBA Teams</h3>'
			}
		},
		{
			name: 'nhl',
			displayKey: 'team',
			source: nhl.ttAdapter(),
			templates: {
				header: '<h3>NHL Teams</h3>'
			}
		});

	};

	var handleBootstrapSwitch = function () {

		$('.switch-radio1').on('switch-change', function () {
			$('.switch-radio1').bootstrapSwitch('toggleRadioState');
		});

		// or
		$('.switch-radio1').on('switch-change', function () {
			$('.switch-radio1').bootstrapSwitch('toggleRadioStateAllowUncheck');
		});

		// or
		$('.switch-radio1').on('switch-change', function () {
			$('.switch-radio1').bootstrapSwitch('toggleRadioStateAllowUncheck', false);
		});

	};

	var handleBootstrapTouchSpin = function () {

		$(".touchspin").each(function () {
			var size = null;
			var params = {};
			for (var i = 0, attrs = this.attributes, l = attrs.length; i < l; i++) {
				var attr = attrs.item(i).nodeName;
				if (attr.substring(0, 5) === "data-") {
					var paramName = attr.substring(5).replace("-", "_");
					switch (paramName) {
						case "min":
						case "max":
						case "stepinterval":
						case "maxboostedstep":
						case "decimals":
						case "boostat":
							params[paramName] = parseInt($(this).attr(attr));
							break;
						case "step":
							params[paramName] = parseFloat($(this).attr(attr));
							break;
						case "size":
							size = $(this).attr(attr);
							break;
						default:
							params[paramName] = $(this).attr(attr);
							break;
					}
				}
			}
			$(this).TouchSpin(params);
			if (size) {
				$(this).closest('.bootstrap-touchspin').addClass(size);
			}
		});

	};

	var handleBootstrapMaxlength = function () {
		$('#maxlength_defaultconfig').maxlength({
			limitReachedClass: "label label-danger",
		})

		$('#maxlength_thresholdconfig').maxlength({
			limitReachedClass: "label label-danger",
			threshold: 20
		});

		$('#maxlength_alloptions').maxlength({
			alwaysShow: true,
			warningClass: "label label-success",
			limitReachedClass: "label label-danger",
			separator: ' out of ',
			preText: 'You typed ',
			postText: ' chars available.',
			validate: true
		});

		$('#maxlength_textarea').maxlength({
			limitReachedClass: "label label-danger",
			alwaysShow: true
		});

		$('#maxlength_placement').maxlength({
			limitReachedClass: "label label-danger",
			alwaysShow: true,
			placement: Metronic.isRTL() ? 'top-right' : 'top-left'
		});
	};

	var handleSpinners = function () {
		$(".form-spinner").each(function () {
			var size = null;
			var params = {};
			for (var i = 0, attrs = this.attributes, l = attrs.length; i < l; i++) {
				var attr = attrs.item(i).nodeName;
				if (attr.substring(0, 5) === "data-") {
					var paramName = attr.substring(5).replace(/\-/g, "_");
					switch (paramName) {
						case "size":
							size = $(this).attr(attr);
							break;
						default:
							params[paramName] = $(this).attr(attr);
							break;
					}
				}
			}
			$(this).spinner(params);
			if (size) {
				$(this).closest('.input-group').addClass(size);
			}
		});
	};

	var handleTagsInput = function () {
		if (!jQuery().tagsInput) {
			return;
		}
		$('.tags').each(function () {
			var params = {
				width: 'auto'
			};
			for (var i = 0, attrs = this.attributes, l = attrs.length; i < l; i++) {
				var attr = attrs.item(i).nodeName;
				if (attr.substring(0, 5) === 'data-') {
					var paramName = attr.substring(5);
					var start = 0;
					var n = paramName.indexOf('-', start);
					while (n >= 0 && paramName.length >= (n + 1)) {
						if (paramName.length >= (n + 3)) {
							paramName = paramName.substring(0, n)
									+ paramName.substring(n + 1, n + 2).toUpperCase()
									+ paramName.substring(n + 2);
						}
						start = n + 1;
						n = paramName.indexOf('-', start);
					}
					params[paramName] = $(this).attr(attr);
				}
			}
			$(this).tagsInput(params);

			var id = $(this).attr('id');
			$('#' + id + '_tag').focus(function () {
				$('#' + id + '_tagsinput').addClass('active');
			});
			$('#' + id + '_tag').blur(function () {
				$('#' + id + '_tagsinput').removeClass('active');
			});

		});
	};

	/**
	 * https://github.com/RobinHerbots/jquery.inputmask
	 */
	var handleInputMasks = function () {
		$.extend($.inputmask.defaults, {
			'autounmask': true
		});

		$('.mask_date').inputmask('d/m/y', {
			autoUnmask: true,
			placeholder: 'dd/mm/yyyy'
		});

		$('.mask_phone').inputmask('phone', {
			url: basePath + '/assets/global/plugins/jquery-inputmask/inputmask/phone-codes/phone-codes.json',
			onKeyValidation: function () { //show some metadata in the console
				if ($(this).inputmask('getmetadata') != undefined) {
					console.log($(this).inputmask('getmetadata')['name_en']);
				}
			}
		});

		$('.mask_currency').inputmask('currency', {
            rightAlign: 0,
			prefix: '',
			suffix: ' €', // TODO: replace by default currency
            radixPoint: ",",
            groupSeparator: ' '
		});

		$('.mask_ipv4').ipAddress();
		$('.mask_ipv6').ipAddress({
			v: 6
		});
	};

	var handlePasswordStrengthChecker = function () {
		var initialized = false;
		var input = $("#password_strength");

		input.keydown(function () {
			if (initialized === false) {
				// set base options
				input.pwstrength({
					raisePower: 1.4,
					minChar: 8,
					verdicts: ["Weak", "Normal", "Medium", "Strong", "Very Strong"],
					scores: [17, 26, 40, 50, 60]
				});

				// add your own rule to calculate the password strength
				input.pwstrength("addRule", "demoRule", function (options, word, score) {
					return word.match(/[a-z].[0-9]/) && score;
				}, 10, true);

				// set as initialized
				initialized = true;
			}
		});
	};

	return {
		//main function to initiate the module
		init: function () {
//            handleTwitterTypeahead();
//            handleTwitterTypeaheadModal();
//            handleBootstrapSwitch();
			handleBootstrapTouchSpin();
//            handleBootstrapMaxlength();
			handleSpinners();
			handleTagsInput();
			handleInputMasks();
//            handlePasswordStrengthChecker();
		}
	};

}();
