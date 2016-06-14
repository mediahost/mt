var HtmlEditors = function () {

	var handleWysihtml5 = function () {
		if (!jQuery().wysihtml5) {
			return;
		}

		//	https://github.com/xing/wysihtml5/blob/master/parser_rules/simple.js
		var wysihtml5ParserRules = {
			tags: {
				h1: {},
				h2: {},
				h3: {},
				h4: {},
				h5: {},
				h6: {},
				strong: {},
				b: {},
				i: {},
				em: {},
				br: {},
				p: {},
				div: {},
				span: {},
				ul: {},
				ol: {},
				li: {},
				img: {
					check_attributes: {
						width: "numbers",
						alt: "alt",
						src: "url",
						height: "numbers"
					}
				},
				a: {
					set_attributes: {
						target: "_blank",
						rel: "nofollow"
					},
					check_attributes: {
						href: "url" // important to avoid XSS
					}
				}
			}
		};

		if ($('.wysihtml5').size() > 0) {
			var options = {
				stylesheets: [basePath + '/assets/global/plugins/bootstrap-wysihtml5/wysiwyg-color.min.css'],
				html: true,
				parserRules: wysihtml5ParserRules,
				parser: wysihtml5.dom.parse
			};
			if ($('.wysihtml5').hasClass('page-html-content')) {
				options.stylesheets = [];
				options.stylesheets.push('https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|PT+Sans+Narrow|Source+Sans+Pro:200,300,400,600,700,900&amp;subset=all');
				options.stylesheets.push(basePath + '/assets/frontend/css/style.css');
				options.stylesheets.push(basePath + '/assets/frontend/css/style-shop.css');
			}
			$('.wysihtml5').wysihtml5(options);
		}
	};

	return {
		//main function to initiate the module
		init: function () {

			handleWysihtml5();

		}

	};

}();
