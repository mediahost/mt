jQuery(document).ready(function () {

	// renderer settings
	LiveForm.options.errorMessageClass = 'help-block help-block-error';
	LiveForm.options.errorMessagePrefix = '';

});

if (applets.googleAnalytics) {
	(function (i, s, o, g, r, a, m) {
		i['GoogleAnalyticsObject'] = r;
		i[r] = i[r] || function () {
			(i[r].q = i[r].q || []).push(arguments)
		}, i[r].l = 1 * new Date();
		a = s.createElement(o),
				m = s.getElementsByTagName(o)[0];
		a.async = 1;
		a.src = g;
		m.parentNode.insertBefore(a, m)
	})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
	ga('create', applets.googleAnalytics, 'auto');
	ga('send', 'pageview');
}

if (applets.smartSupp) {
	var _smartsupp = _smartsupp || {};
	_smartsupp.key = applets.smartSupp;
	window.smartsupp || (function (d) {
		var o = smartsupp = function () {
			o._.push(arguments)
		}, s = d.getElementsByTagName('script')[0], c = d.createElement('script');
		o._ = [];
		c.async = true;
		c.type = 'text/javascript';
		c.charset = 'utf-8';
		c.src = '//www.smartsuppchat.com/loader.js?';
		s.parentNode.insertBefore(c, s);
	})(document);
}

if (applets.smartLook) {
	window.smartlook || (function (d) {
		var o = smartlook = function () {
			o.api.push(arguments)
		}, s = d.getElementsByTagName('script')[0];
		var c = d.createElement('script');
		o.api = new Array();
		c.async = true;
		c.type = 'text/javascript';
		c.charset = 'utf-8';
		c.src = '//rec.getsmartlook.com/bundle.js';
		s.parentNode.insertBefore(c, s);
	})(document);
	smartlook('init', applets.smartLook, {
		"host": "s2.getsmartlook.com"
	});
}

if (applets.facebookApplet) {
	(function (d, s, id) {
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id))
			return;
		;
		js = d.createElement(s);
		js.id = id;
		js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.4&appId=" + applets.facebookApplet;
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
}


