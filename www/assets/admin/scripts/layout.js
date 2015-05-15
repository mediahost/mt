/**
 Core script to handle the entire theme and core functions
 **/
var Layout = function () {

	var layoutImgPath = 'admin/img/';

	var layoutCssPath = 'admin/css/';

	//* BEGIN:CORE HANDLERS *//
	// this function handles responsive layout on screen size resize or mobile device rotate.

	// Set proper height for sidebar and content. The content and sidebar height must be synced always.
	var handleSidebarAndContentHeight = function () {
		var content = $('.page-content');
		var sidebar = $('.page-sidebar');
		var body = $('body');
		var height;

		if (body.hasClass("page-footer-fixed") === true && body.hasClass("page-sidebar-fixed") === false) {
			var available_height = Metronic.getViewPort().height - $('.page-footer').outerHeight() - $('.page-header').outerHeight();
			if (content.height() < available_height) {
				content.attr('style', 'min-height:' + available_height + 'px');
			}
		} else {
			if (body.hasClass('page-sidebar-fixed')) {
				height = _calculateFixedSidebarViewportHeight();
				if (body.hasClass('page-footer-fixed') === false) {
					height = height - $('.page-footer').outerHeight();
				}
			} else {
				var headerHeight = $('.page-header').outerHeight();
				var footerHeight = $('.page-footer').outerHeight();

				if (Metronic.getViewPort().width < 992) {
					height = Metronic.getViewPort().height - headerHeight - footerHeight;
				} else {
					height = sidebar.height() + 20;
				}

				if ((height + headerHeight + footerHeight) <= Metronic.getViewPort().height) {
					height = Metronic.getViewPort().height - headerHeight - footerHeight;
				}
			}
			content.attr('style', 'min-height:' + height + 'px');
		}
	};

	// Handle sidebar menu links
	var handleSidebarMenuActiveLink = function (mode, el) {
		var url = location.hash.toLowerCase();

		var menu = $('.page-sidebar-menu');

		if (mode === 'click' || mode === 'set') {
			el = $(el);
		} else if (mode === 'match') {
			menu.find("li > a").each(function () {
				var path = $(this).attr("href").toLowerCase();
				// url match condition         
				if (path.length > 1 && url.substr(1, path.length - 1) == path.substr(1)) {
					el = $(this);
					return;
				}
			});
		}

		if (!el || el.size() == 0) {
			return;
		}

		if (el.attr('href').toLowerCase() === 'javascript:;' || el.attr('href').toLowerCase() === '#') {
			return;
		}

		var slideSpeed = parseInt(menu.data("slide-speed"));
		var keepExpand = menu.data("keep-expanded");

		// disable active states
		menu.find('li.active').removeClass('active');
		menu.find('li > a > .selected').remove();

		if (menu.hasClass('page-sidebar-menu-hover-submenu') === false) {
			menu.find('li.open').each(function () {
				if ($(this).children('.sub-menu').size() === 0) {
					$(this).removeClass('open');
					$(this).find('> a > .arrow.open').removeClass('open');
				}
			});
		} else {
			menu.find('li.open').removeClass('open');
		}

		el.parents('li').each(function () {
			$(this).addClass('active');
			$(this).find('> a > span.arrow').addClass('open');

			if ($(this).parent('ul.page-sidebar-menu').size() === 1) {
				$(this).find('> a').append('<span class="selected"></span>');
			}

			if ($(this).children('ul.sub-menu').size() === 1) {
				$(this).addClass('open');
			}
		});

		if (mode === 'click') {
			if (Metronic.getViewPort().width < 992 && $('.page-sidebar').hasClass("in")) { // close the menu on mobile view while laoding a page 
				$('.page-header .responsive-toggler').click();
			}
		}
	};

	// Handle sidebar menu
	var handleSidebarMenu = function () {
		// handle sidebar link click
		jQuery('.page-sidebar').on('click', 'li > a', function (e) {
			var hasSubMenu = $(this).next().hasClass('sub-menu');

			if (Metronic.getViewPort().width >= 992 && $(this).parents('.page-sidebar-menu-hover-submenu').size() === 1) { // exit of hover sidebar menu
				return;
			}

			if (hasSubMenu === false) {
				if (Metronic.getViewPort().width < 992 && $('.page-sidebar').hasClass("in")) { // close the menu on mobile view while laoding a page 
					$('.page-header .responsive-toggler').click();
				}
				return;
			}

			if ($(this).next().hasClass('sub-menu always-open')) {
				return;
			}

			var parent = $(this).parent().parent();
			var the = $(this);
			var menu = $('.page-sidebar-menu');
			var sub = jQuery(this).next();

			var autoScroll = menu.data("auto-scroll");
			var slideSpeed = parseInt(menu.data("slide-speed"));
			var keepExpand = menu.data("keep-expanded");

			if (keepExpand !== true) {
				parent.children('li.open').children('a').children('.arrow').removeClass('open');
				parent.children('li.open').children('.sub-menu:not(.always-open)').slideUp(slideSpeed);
				parent.children('li.open').removeClass('open');
			}

			var slideOffeset = -200;

			if (sub.is(":visible")) {
				jQuery('.arrow', jQuery(this)).removeClass("open");
				jQuery(this).parent().removeClass("open");
				sub.slideUp(slideSpeed, function () {
					if (autoScroll === true && $('body').hasClass('page-sidebar-closed') === false) {
						if ($('body').hasClass('page-sidebar-fixed')) {
							menu.slimScroll({
								'scrollTo': (the.position()).top
							});
						} else {
							Metronic.scrollTo(the, slideOffeset);
						}
					}
					handleSidebarAndContentHeight();
				});
			} else if (hasSubMenu) {
				jQuery('.arrow', jQuery(this)).addClass("open");
				jQuery(this).parent().addClass("open");
				sub.slideDown(slideSpeed, function () {
					if (autoScroll === true && $('body').hasClass('page-sidebar-closed') === false) {
						if ($('body').hasClass('page-sidebar-fixed')) {
							menu.slimScroll({
								'scrollTo': (the.position()).top
							});
						} else {
							Metronic.scrollTo(the, slideOffeset);
						}
					}
					handleSidebarAndContentHeight();
				});
			}

			e.preventDefault();
		});

		// handle ajax links within sidebar menu
		jQuery('.page-sidebar').on('click', ' li > a.ajaxify', function (e) {
			e.preventDefault();
			Metronic.scrollTop();

			var url = $(this).attr("href");
			var menuContainer = jQuery('.page-sidebar ul');
			var pageContent = $('.page-content');
			var pageContentBody = $('.page-content .page-content-body');

			menuContainer.children('li.active').removeClass('active');
			menuContainer.children('arrow.open').removeClass('open');

			$(this).parents('li').each(function () {
				$(this).addClass('active');
				$(this).children('a > span.arrow').addClass('open');
			});
			$(this).parents('li').addClass('active');

			if (Metronic.getViewPort().width < 992 && $('.page-sidebar').hasClass("in")) { // close the menu on mobile view while laoding a page 
				$('.page-header .responsive-toggler').click();
			}

			Metronic.startPageLoading();

			var the = $(this);

			$.ajax({
				type: "GET",
				cache: false,
				url: url,
				dataType: "html",
				success: function (res) {
					if (the.parents('li.open').size() === 0) {
						$('.page-sidebar-menu > li.open > a').click();
					}

					Metronic.stopPageLoading();
					pageContentBody.html(res);
					Layout.fixContentHeight(); // fix content height
					Metronic.initAjax(); // initialize core stuff
				},
				error: function (xhr, ajaxOptions, thrownError) {
					Metronic.stopPageLoading();
					pageContentBody.html('<h4>Could not load the requested content.</h4>');
				}
			});
		});

		// handle ajax link within main content
		jQuery('.page-content').on('click', '.ajaxify', function (e) {
			e.preventDefault();
			Metronic.scrollTop();

			var url = $(this).attr("href");
			var pageContent = $('.page-content');
			var pageContentBody = $('.page-content .page-content-body');

			Metronic.startPageLoading();

			if (Metronic.getViewPort().width < 992 && $('.page-sidebar').hasClass("in")) { // close the menu on mobile view while laoding a page 
				$('.page-header .responsive-toggler').click();
			}

			$.ajax({
				type: "GET",
				cache: false,
				url: url,
				dataType: "html",
				success: function (res) {
					Metronic.stopPageLoading();
					pageContentBody.html(res);
					Layout.fixContentHeight(); // fix content height
					Metronic.initAjax(); // initialize core stuff
				},
				error: function (xhr, ajaxOptions, thrownError) {
					pageContentBody.html('<h4>Could not load the requested content.</h4>');
					Metronic.stopPageLoading();
				}
			});
		});

		// handle sidebar hover effect        
		handleFixedSidebarHoverEffect();

		// handle the search bar close
		$('.page-sidebar').on('click', '.sidebar-search .remove', function (e) {
			e.preventDefault();
			$('.sidebar-search').removeClass("open");
		});

		// handle the search query submit on enter press
		$('.page-sidebar .sidebar-search').on('keypress', 'input.form-control', function (e) {
			if (e.which == 13) {
				$('.sidebar-search').submit();
				return false; //<---- Add this line
			}
		});

		// handle the search submit(for sidebar search and responsive mode of the header search)
		$('.sidebar-search .submit').on('click', function (e) {
			e.preventDefault();
			if ($('body').hasClass("page-sidebar-closed")) {
				if ($('.sidebar-search').hasClass('open') === false) {
					if ($('.page-sidebar-fixed').size() === 1) {
						$('.page-sidebar .sidebar-toggler').click(); //trigger sidebar toggle button
					}
					$('.sidebar-search').addClass("open");
				} else {
					$('.sidebar-search').submit();
				}
			} else {
				$('.sidebar-search').submit();
			}
		});

		// handle close on body click
		if ($('.sidebar-search').size() !== 0) {
			$('.sidebar-search .input-group').on('click', function (e) {
				e.stopPropagation();
			});

			$('body').on('click', function () {
				if ($('.sidebar-search').hasClass('open')) {
					$('.sidebar-search').removeClass("open");
				}
			});
		}
	};

	// Helper function to calculate sidebar height for fixed sidebar layout.
	var _calculateFixedSidebarViewportHeight = function () {
		var sidebarHeight = Metronic.getViewPort().height - $('.page-header').outerHeight();
		if ($('body').hasClass("page-footer-fixed")) {
			sidebarHeight = sidebarHeight - $('.page-footer').outerHeight();
		}

		return sidebarHeight;
	};

	// Handles fixed sidebar
	var handleFixedSidebar = function () {
		var menu = $('.page-sidebar-menu');

		Metronic.destroySlimScroll(menu);

		if ($('.page-sidebar-fixed').size() === 0) {
			handleSidebarAndContentHeight();
			return;
		}

		if (Metronic.getViewPort().width >= 992) {
			menu.attr("data-height", _calculateFixedSidebarViewportHeight());
			Metronic.initSlimScroll(menu);
			handleSidebarAndContentHeight();
		}
	};

	// Handles sidebar toggler to close/hide the sidebar.
	var handleFixedSidebarHoverEffect = function () {
		var body = $('body');
		if (body.hasClass('page-sidebar-fixed')) {
			$('.page-sidebar').on('mouseenter', function () {
				if (body.hasClass('page-sidebar-closed')) {
					$(this).find('.page-sidebar-menu').removeClass('page-sidebar-menu-closed');
				}
			}).on('mouseleave', function () {
				if (body.hasClass('page-sidebar-closed')) {
					$(this).find('.page-sidebar-menu').addClass('page-sidebar-menu-closed');
				}
			});
		}
	};

	// Hanles sidebar toggler
	var handleSidebarToggler = function () {
		var body = $('body');
		// remembering closed sidebar
//		if ($.cookie && $.cookie('sidebar_closed') === '1' && Metronic.getViewPort().width >= 992) {
//			$('body').addClass('page-sidebar-closed');
//			$('.page-sidebar-menu').addClass('page-sidebar-menu-closed');
//		}

		// handle sidebar show/hide
		$('body').on('click', '.sidebar-toggler', function (e) {
			var sidebar = $('.page-sidebar');
			var sidebarMenu = $('.page-sidebar-menu');
			var url = $('.sidebar-toggler').attr('data-url');
			var value = null;
			$(".sidebar-search", sidebar).removeClass("open");

			if (body.hasClass("page-sidebar-closed")) {
				body.removeClass("page-sidebar-closed");
				sidebarMenu.removeClass("page-sidebar-menu-closed");
				value = 0;
//				if ($.cookie) {
//					$.cookie('sidebar_closed', '0');
//				}
			} else {
				body.addClass("page-sidebar-closed");
				sidebarMenu.addClass("page-sidebar-menu-closed");
				if (body.hasClass("page-sidebar-fixed")) {
					sidebarMenu.trigger("mouseleave");
				}
				value = 1;
//				if ($.cookie) {
//					$.cookie('sidebar_closed', '1');
//				}
			}
			if (url) {
				if (url.match(/\?/)) {
					url += '&value=' + value;
				} else {
					url += '?value=' + value;
				}
				$.ajax(url);
			}

			$(window).trigger('resize');
		});
	};

	// Handles the horizontal menu
	var handleHorizontalMenu = function () {
		//handle tab click
		$('.page-header').on('click', '.hor-menu a[data-toggle="tab"]', function (e) {
			e.preventDefault();
			var nav = $(".hor-menu .nav");
			var active_link = nav.find('li.current');
			$('li.active', active_link).removeClass("active");
			$('.selected', active_link).remove();
			var new_link = $(this).parents('li').last();
			new_link.addClass("current");
			new_link.find("a:first").append('<span class="selected"></span>');
		});

		// handle search box expand/collapse        
		$('.page-header').on('click', '.search-form', function (e) {
			$(this).addClass("open");
			$(this).find('.form-control').focus();

			$('.page-header .search-form .form-control').on('blur', function (e) {
				$(this).closest('.search-form').removeClass("open");
				$(this).unbind("blur");
			});
		});

		// handle hor menu search form on enter press
		$('.page-header').on('keypress', '.hor-menu .search-form .form-control', function (e) {
			if (e.which == 13) {
				$(this).closest('.search-form').submit();
				return false;
			}
		});

		// handle header search button click
		$('.page-header').on('mousedown', '.search-form.open .submit', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).closest('.search-form').submit();
		});

		// handle hover dropdown menu for desktop devices only
		$('[data-hover="megamenu-dropdown"]').not('.hover-initialized').each(function () {
			$(this).dropdownHover();
			$(this).addClass('hover-initialized');
		});

		$(document).on('click', '.mega-menu-dropdown .dropdown-menu', function (e) {
			e.stopPropagation();
		});
	};

	// Handles Bootstrap Tabs.
	var handleTabs = function () {
		// fix content height on tab click
		$('body').on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
			handleSidebarAndContentHeight();
		});
	};

	// Handles the go to top button at the footer
	var handleGoTop = function () {
		var offset = 300;
		var duration = 500;

		if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {  // ios supported
			$(window).bind("touchend touchcancel touchleave", function (e) {
				if ($(this).scrollTop() > offset) {
					$('.scroll-to-top').fadeIn(duration);
				} else {
					$('.scroll-to-top').fadeOut(duration);
				}
			});
		} else {  // general 
			$(window).scroll(function () {
				if ($(this).scrollTop() > offset) {
					$('.scroll-to-top').fadeIn(duration);
				} else {
					$('.scroll-to-top').fadeOut(duration);
				}
			});
		}

		$('.scroll-to-top').click(function (e) {
			e.preventDefault();
			$('html, body').animate({scrollTop: 0}, duration);
			return false;
		});
	};

	// Hanlde 100% height elements(block, portlet, etc)
	var handle100HeightContent = function () {

		var target = $('.full-height-content');
		var height;

		height = Metronic.getViewPort().height -
				$('.page-header').outerHeight(true) -
				$('.page-footer').outerHeight(true) -
				$('.page-title').outerHeight(true) -
				$('.page-bar').outerHeight(true);

		if (target.hasClass('portlet')) {
			var portletBody = target.find('.portlet-body');

			if (Metronic.getViewPort().width < 992) {
				Metronic.destroySlimScroll(portletBody.find('.full-height-content-body')); // destroy slimscroll 
				return;
			}

			height = height -
					target.find('.portlet-title').outerHeight(true) -
					parseInt(target.find('.portlet-body').css('padding-top')) -
					parseInt(target.find('.portlet-body').css('padding-bottom')) - 2;

			if (target.hasClass("full-height-content-scrollable")) {
				height = height - 35;
				portletBody.find('.full-height-content-body').css('height', height);
				Metronic.initSlimScroll(portletBody.find('.full-height-content-body'));
			} else {
				portletBody.css('min-height', height);
			}
		} else {
			if (Metronic.getViewPort().width < 992) {
				Metronic.destroySlimScroll(target.find('.full-height-content-body')); // destroy slimscroll 
				return;
			}

			if (target.hasClass("full-height-content-scrollable")) {
				height = height - 35;
				target.find('.full-height-content-body').css('height', height);
				Metronic.initSlimScroll(target.find('.full-height-content-body'));
			} else {
				target.css('min-height', height);
			}
		}
	};

	// Handle Theme Settings
	var handleCustomizeTheme = function () {

		var panel = $('.theme-panel');

		if ($('body').hasClass('page-boxed') === false) {
			$('.layout-option', panel).val("fluid");
		}

		$('.sidebar-option', panel).val("default");
		$('.page-header-option', panel).val("fixed");
		$('.page-footer-option', panel).val("default");
		if ($('.sidebar-pos-option').attr("disabled") === false) {
			$('.sidebar-pos-option', panel).val(Metronic.isRTL() ? 'right' : 'left');
		}

		//handle theme layout
		var resetLayout = function () {
			$("body").
					removeClass("page-boxed").
					removeClass("page-footer-fixed").
					removeClass("page-sidebar-fixed").
					removeClass("page-header-fixed").
					removeClass("page-sidebar-reversed");

			$('.page-header > .page-header-inner').removeClass("container");

			if ($('.page-container').parent(".container").size() === 1) {
				$('.page-container').insertAfter('body > .clearfix');
			}

			if ($('.page-footer > .container').size() === 1) {
				$('.page-footer').html($('.page-footer > .container').html());
			} else if ($('.page-footer').parent(".container").size() === 1) {
				$('.page-footer').insertAfter('.page-container');
				$('.scroll-to-top').insertAfter('.page-footer');
			}

			$(".top-menu > .navbar-nav > li.dropdown").removeClass("dropdown-dark");

			$('body > .container').remove();
		};

		var lastSelectedLayout = '';

		var setLayout = function () {

			var layoutOption = $('.layout-option', panel).val();
			var sidebarOption = $('.sidebar-option', panel).val();
			var headerOption = $('.page-header-option', panel).val();
			var footerOption = $('.page-footer-option', panel).val();
			var sidebarPosOption = $('.sidebar-pos-option', panel).val();
			var sidebarStyleOption = $('.sidebar-style-option', panel).val();
			var sidebarMenuOption = $('.sidebar-menu-option', panel).val();
			var headerTopDropdownStyle = $('.page-header-top-dropdown-style-option', panel).val();
			var url = $('.theme-options').attr('data-url');

			if (sidebarOption == "fixed" && headerOption == "default") {
				alert('Default Header with Fixed Sidebar option is not supported. Proceed with Fixed Header with Fixed Sidebar.');
				$('.page-header-option', panel).val("fixed");
				$('.sidebar-option', panel).val("fixed");
				sidebarOption = 'fixed';
				headerOption = 'fixed';
			}

			resetLayout(); // reset layout to default state

			if (layoutOption === "boxed") {
				$("body").addClass("page-boxed");

				// set header
				$('.page-header > .page-header-inner').addClass("container");
				var cont = $('body > .clearfix').after('<div class="container"></div>');

				// set content
				$('.page-container').appendTo('body > .container');

				// set footer
				if (footerOption === 'fixed') {
					$('.page-footer').html('<div class="container">' + $('.page-footer').html() + '</div>');
				} else {
					$('.page-footer').appendTo('body > .container');
				}
			}

			if (lastSelectedLayout != layoutOption) {
				//layout changed, run responsive handler: 
				Metronic.runResizeHandlers();
			}
			lastSelectedLayout = layoutOption;

			//header
			if (headerOption === 'fixed') {
				$("body").addClass("page-header-fixed");
				$(".page-header").removeClass("navbar-static-top").addClass("navbar-fixed-top");
			} else {
				$("body").removeClass("page-header-fixed");
				$(".page-header").removeClass("navbar-fixed-top").addClass("navbar-static-top");
			}

			//sidebar
			if ($('body').hasClass('page-full-width') === false) {
				if (sidebarOption === 'fixed') {
					$("body").addClass("page-sidebar-fixed");
					$("page-sidebar-menu").addClass("page-sidebar-menu-fixed");
					$("page-sidebar-menu").removeClass("page-sidebar-menu-default");
					Layout.initFixedSidebarHoverEffect();
				} else {
					$("body").removeClass("page-sidebar-fixed");
					$("page-sidebar-menu").addClass("page-sidebar-menu-default");
					$("page-sidebar-menu").removeClass("page-sidebar-menu-fixed");
					$('.page-sidebar-menu').unbind('mouseenter').unbind('mouseleave');
				}
			}

			// top dropdown style
			if (headerTopDropdownStyle === 'dark') {
				$(".top-menu > .navbar-nav > li.dropdown").addClass("dropdown-dark");
			} else {
				$(".top-menu > .navbar-nav > li.dropdown").removeClass("dropdown-dark");
			}

			//footer 
			if (footerOption === 'fixed') {
				$("body").addClass("page-footer-fixed");
			} else {
				$("body").removeClass("page-footer-fixed");
			}

			//sidebar style
			if (sidebarStyleOption === 'light') {
				$(".page-sidebar-menu").addClass("page-sidebar-menu-light");
			} else {
				$(".page-sidebar-menu").removeClass("page-sidebar-menu-light");
			}

			//sidebar menu 
			if (sidebarMenuOption === 'hover') {
				if (sidebarOption == 'fixed') {
					$('.sidebar-menu-option', panel).val("accordion");
					sidebarMenuOption = 'accordion';
					alert("Hover Sidebar Menu is not compatible with Fixed Sidebar Mode. Select Default Sidebar Mode Instead.");
				} else {
					$(".page-sidebar-menu").addClass("page-sidebar-menu-hover-submenu");
				}
			} else {
				$(".page-sidebar-menu").removeClass("page-sidebar-menu-hover-submenu");
			}

			//sidebar position
			if (Metronic.isRTL()) {
				if (sidebarPosOption === 'left') {
					$("body").addClass("page-sidebar-reversed");
					$('#frontend-link').tooltip('destroy').tooltip({
						placement: 'right'
					});
				} else {
					$("body").removeClass("page-sidebar-reversed");
					$('#frontend-link').tooltip('destroy').tooltip({
						placement: 'left'
					});
				}
			} else {
				if (sidebarPosOption === 'right') {
					$("body").addClass("page-sidebar-reversed");
					$('#frontend-link').tooltip('destroy').tooltip({
						placement: 'left'
					});
				} else {
					$("body").removeClass("page-sidebar-reversed");
					$('#frontend-link').tooltip('destroy').tooltip({
						placement: 'right'
					});
				}
			}

			Layout.fixContentHeight(); // fix content height            
			Layout.initFixedSidebar(); // reinitialize fixed sidebar

			if (url) {
				if (url.match(/\?/)) {
					url += '&';
				} else {
					url += '?';
				}
				url += 'layoutOption=' + layoutOption + '&';
				url += 'sidebarOption=' + sidebarOption + '&';
				url += 'headerOption=' + headerOption + '&';
				url += 'footerOption=' + footerOption + '&';
				url += 'sidebarPosOption=' + sidebarPosOption + '&';
				url += 'sidebarStyleOption=' + sidebarStyleOption + '&';
				url += 'sidebarMenuOption=' + sidebarMenuOption;
				console.log(url);
				$.ajax(url);
			}
		};

		// handle theme colors
		var setColor = function (color) {
			var color_ = (Metronic.isRTL() ? color + '-rtl' : color);
			$('#style_color').attr("href", Layout.getLayoutCssPath() + 'themes/' + color_ + ".css");
			if (color == 'light2') {
				$('.page-logo img').attr('src', Layout.getLayoutImgPath() + 'logo-invert.png');
			} else {
				$('.page-logo img').attr('src', Layout.getLayoutImgPath() + 'logo.png');
			}
		};

		$('.toggler', panel).click(function () {
			$('.toggler').hide();
			$('.toggler-close').show();
			$('.theme-panel > .theme-options').show();
		});

		$('.toggler-close', panel).click(function () {
			$('.toggler').show();
			$('.toggler-close').hide();
			$('.theme-panel > .theme-options').hide();
		});

		$('.theme-colors > ul > li', panel).click(function () {
			var color = $(this).attr("data-style");
			setColor(color);
			$.ajax($(this).attr("data-url"));
			$('ul > li', panel).removeClass("current");
			$(this).addClass("current");
		});

		// set default theme options:

		if ($("body").hasClass("page-boxed")) {
			$('.layout-option', panel).val("boxed");
		}

		if ($("body").hasClass("page-sidebar-fixed")) {
			$('.sidebar-option', panel).val("fixed");
		}

		if ($("body").hasClass("page-header-fixed")) {
			$('.page-header-option', panel).val("fixed");
		}

		if ($("body").hasClass("page-footer-fixed")) {
			$('.page-footer-option', panel).val("fixed");
		}

		if ($("body").hasClass("page-sidebar-reversed")) {
			$('.sidebar-pos-option', panel).val("right");
		}

		if ($(".page-sidebar-menu").hasClass("page-sidebar-menu-light")) {
			$('.sidebar-style-option', panel).val("light");
		}

		if ($(".page-sidebar-menu").hasClass("page-sidebar-menu-hover-submenu")) {
			$('.sidebar-menu-option', panel).val("hover");
		}

		var sidebarOption = $('.sidebar-option', panel).val();
		var headerOption = $('.page-header-option', panel).val();
		var footerOption = $('.page-footer-option', panel).val();
		var sidebarPosOption = $('.sidebar-pos-option', panel).val();
		var sidebarStyleOption = $('.sidebar-style-option', panel).val();
		var sidebarMenuOption = $('.sidebar-menu-option', panel).val();

		$('.layout-option, .page-header-option, .page-header-top-dropdown-style-option, .sidebar-option, .page-footer-option, .sidebar-pos-option, .sidebar-style-option, .sidebar-menu-option', panel).change(setLayout);
	};
	//* END:CORE HANDLERS *//

	return {
		// Main init methods to initialize the layout
		//IMPORTANT!!!: Do not modify the core handlers call order.

		initHeader: function () {
			handleHorizontalMenu(); // handles horizontal menu    
		},
		setSidebarMenuActiveLink: function (mode, el) {
			handleSidebarMenuActiveLink(mode, el);
		},
		initSidebar: function () {
			//layout handlers
			handleFixedSidebar(); // handles fixed sidebar menu
			handleSidebarMenu(); // handles main menu
			handleSidebarToggler(); // handles sidebar hide/show

			if (Metronic.isAngularJsApp()) {
				handleSidebarMenuActiveLink('match'); // init sidebar active links 
			}

			Metronic.addResizeHandler(handleFixedSidebar); // reinitialize fixed sidebar on window resize
		},
		initContent: function () {
			handle100HeightContent(); // handles 100% height elements(block, portlet, etc)
			handleTabs(); // handle bootstrah tabs

			Metronic.addResizeHandler(handleSidebarAndContentHeight); // recalculate sidebar & content height on window resize
			Metronic.addResizeHandler(handle100HeightContent); // reinitialize content height on window resize 
		},
		initFooter: function () {
			handleGoTop(); //handles scroll to top functionality in the footer
		},
		initDesignCustomizer: function () {
			handleCustomizeTheme(); // handles style customer tool
		},
		init: function () {
			this.initHeader();
			this.initSidebar();
			this.initContent();
			this.initFooter();
			this.initDesignCustomizer();
		},
		//public function to fix the sidebar and content height accordingly
		fixContentHeight: function () {
			handleSidebarAndContentHeight();
		},
		initFixedSidebarHoverEffect: function () {
			handleFixedSidebarHoverEffect();
		},
		initFixedSidebar: function () {
			handleFixedSidebar();
		},
		getLayoutImgPath: function () {
			return Metronic.getAssetsPath() + layoutImgPath;
		},
		getLayoutCssPath: function () {
			return Metronic.getAssetsPath() + layoutCssPath;
		}
	};

}();