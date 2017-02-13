var Buyout = Buyout || {};

Buyout.init = function () {
	var id = '#frm-modelQuestion-form select[name^=questions]';
	$(document).on('change', id, function (e) {
		var $target = $(e.target);
		Buyout.loadAnswers($target);
	});
	Buyout.loadAnswers($(id));
};

Buyout.loadAnswers = function (el) {
	var configJson = document.getElementById('buyoutConfig');
	var configVars = JSON.parse(configJson.textContent || configJson.innerHTML);
	var questionAnswers = JSON.parse(configVars.questionAnswers);

	var value = el.val();
	var matches = el.attr('name').match(/^questions\[(\d)\]/);
	var number = matches[1];

	if (questionAnswers[value]) {
		$.each(questionAnswers[value], function (key, answer) {
			var id = 'answer-num-' + key + '-' + number;
			$('#' + id + ' label').html(answer);
		});
	}
};