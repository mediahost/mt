var Buyout = Buyout || {};

Buyout.init = function () {
	var configJson = document.getElementById('buyoutConfig');
	var configVars = JSON.parse(configJson.textContent || configJson.innerHTML);
	var questionAnswers = JSON.parse(configVars.questionAnswers);

	$(document).on('change', '#frm-modelQuestion-form select[name^=questions]', function (e) {
		var $target = $(e.target);
		var value = $target.val();
		var matches = $target.attr('name').match(/^questions\[(\d)\]/);
		var number = matches[1];

		if (questionAnswers[value]) {
			$.each(questionAnswers[value], function (key, answer) {
				var id = 'answer-num-' + key + '-' + number;
				$('#' + id + ' label').html(answer);
			});
		}
	});
};