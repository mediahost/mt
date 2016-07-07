var Buyout = function () {

	var $isNewCheckbox = $('#buyout .is-new-checker input');
	var $onlyForQuestionsBlock = $('#buyout .only-for-questions');
	var $onlyForNewBlock = $('#buyout .only-for-new');
	var $questionBlock = $('#buyout .questions .question');
	var $priceBlock = $('#buyout .questions .price');

	var recalculatePrice = function () {
		var fullPriceValue = parseFloat($priceBlock.attr('data-fullPrice'));

		$questionBlock.each(function (i, question) {
			var $question = $(question);
			var radios = $question.find('input:radio');
			radios.each(function (i, radio) {
				var $radio = $(radio);
				if ($radio.is(':checked')) {
					var $answer = $radio.closest('.question-answer');
					var answerValue = parseFloat($answer.attr('data-question-value'));
					fullPriceValue += answerValue;
				}
			});
		});

		fullPriceValue = fullPriceValue < 0 ? 0 : fullPriceValue;
		var formated = number_format(fullPriceValue, 2, ',', ' ') + ' ' + currencySymbol;
		$priceBlock.html(formated);
	};

	var handleForm = function () {
		$(document).on('ifChecked', '#buyout div.i-radio', function () {
			recalculatePrice();
		});

		$(document).on('click', '#buyout .is-new-question li', function () {
			var isNew = $(this).hasClass('new');

			$isNewCheckbox.prop('checked', isNew);
			$.uniform.update();

			if (isNew) {
				$onlyForQuestionsBlock.hide();
				$onlyForNewBlock.show();
			} else {
				$onlyForQuestionsBlock.show();
				$onlyForNewBlock.hide();
			}

			recalculatePrice();
		});
	};

	return {
		init: function () {
			handleForm();
		}
	};

}();

jQuery(document).ready(function () {
	Buyout.init();
});