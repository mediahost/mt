
var Buyout = Buyout || {};

Buyout.init = function (element) {
	$(element).each(function () {
		$this = $(this);
		var bh = new Bloodhound({
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace,
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			remote: {
				url: $this.data('typeahead-url'),
				wildcard: '__QUERY_PLACEHOLDER__'
			}
		});

		$this.typeahead({
			hint: true,
			minLength: 1,
			highlight: true
		},
		{
			display: 'text',
			source: bh,
			templates: {
				empty: '<div class="empty-message">There\'s nothing to loose</div>',
				suggestion: function (payload) {
					return '<div>' + payload.text + '</div>';
				}
			}
		});
	});
};

$.nette.ext('typeahead', {
	load: function (jqXHR, settings) {
		$('[data-typeahead-url]').each(function () {
			$(this).typeahead('destroy');
		});
	},
	complete: function (jqXHR, status, settings) {
		Buyout.init('[data-typeahead-url]');
	}
}, {
	// ... shared context (this) of all callbacks
});