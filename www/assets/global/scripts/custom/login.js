var Login = function () {

	var handleRemember = function () {
		$(document).ready(function () {
			appendRemember($('.rememberme input[name=remember]'));
		});
		$('.rememberme input[name=remember]').on('change', function () {
			appendRemember($(this));
		});

		var appendRemember = function (input) {
			$('.append-remember').each(function () {
				var suffix = '&signIn-OAuthMethod-remember=1';
				if ($(this).hasClass('facebook')) {
					suffix = suffix.replace("OAuthMethod", "facebook");
				} else if ($(this).hasClass('twitter')) {
					suffix = suffix.replace("OAuthMethod", "twitter");
				}
					
				if (input.is(":checked")) {
					$(this).attr('href', $(this).attr('href') + suffix);
				} else {
					var href = $(this).attr('href');
					var end = href.substring(href.length - suffix.length, href.length);
					if (end === suffix) {
						$(this).attr('href', $(this).attr('href').substring(0, href.length - suffix.length));
					}
				}
			});
		};

	};

	return {
		init: function () {
			handleRemember();
		}
	};

}();
