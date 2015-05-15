var ComponentsNoUiSliders = function ()
{

	var handleSkillSlider = function ()
	{
		$('select.noUiSlider').each(function ()
		{
			var slider = new NoUiSlider($(this));
			slider.buildSlider();
		});
	};

	var handleSkillsRange = function ()
	{
		$('select.noUiRanger').each(function ()
		{
			var slider = new NoUiSlider($(this));
			slider.buildRanger();
		});
	};

	return {
		//main function to initiate the module
		init: function ()
		{
			handleSkillSlider();
			handleSkillsRange();
		},
		'SLIDER': 'slider',
		'RANGER': 'ranger'
	};

}();

var NoUiSlider = function (select)
{
	// constants
	this.SLIDER = 'slider';
	this.RANGER = 'ranger';
	this.LOWER = 'lower';
	this.UPPER = 'upper';

	// variables
	this.type;
	this.select = select;
	this.selectProperties = {};
	this.selectOptions = {};
	this.selectOptionsObject = {};
	this.selectMinValue = null;
	this.selectMaxValue = null;
	this.slider = null;
	this.sliderOptions = {};
	this.fixedTooltip;

	this.build = function (type)
	{
		this.setType(type);

		this.setSelect();
		this.select.hide();

		this.createSlider();
		this.setSliderRange(this.selectMinValue, this.selectMaxValue);
		this.setSliderStart(this.getStartOptionValue(), this.getEndOptionValue());

		this.slider.noUiSlider(this.sliderOptions);
		this.select.after(this.slider);

		this.extendTooltips();
		this.extendPips();

		this.slider.on({
			slide: this.sliderOnSlide,
			change: this.sliderOnChange
		});
	};

	this.setType = function (type)
	{
		switch (type) {
			case this.SLIDER:
			case this.RANGER:
				this.type = type;
				break;
		}
	};

	this.setSelect = function ()
	{
		this.setProperties();
		this.setSelectOptions();
	};

	this.setProperties = function ()
	{
		this.selectProperties.id = this.select.attr('id') + '-slider';
		this.selectProperties.dataClass = this.select.attr('data-class');
		this.selectProperties.isTooltip = (this.select.attr('data-tooltip')) === 'true';
		this.selectProperties.isTooltipFixed = (this.select.attr('data-tooltip-fixed')) === 'true';
		this.selectProperties.isPips = (this.select.attr('data-pips')) === 'true';
		this.selectProperties.isExtendEmptyValue = (this.select.attr('data-is-empty-value')) === 'true';
		this.selectProperties.extendEmptyValue = parseInt(this.select.attr('data-empty-value'));
		this.selectProperties.extendEmptyReplace = parseInt(this.select.attr('data-empty-replace'));
	};

	this.setSelectOptions = function ()
	{
		this.selectOptions = this.select.find('option');
		var selectOptionsObject = {};
		this.selectOptions.each(function (key, value) {
			selectOptionsObject[parseInt($(value).val())] = $(value).text();
		});
		this.selectOptionsObject = selectOptionsObject;
		this.setSelectValues();
	};

	this.setSelectValues = function ()
	{
		this.selectMinValue = parseInt(this.selectOptions.first().val());
		this.selectMaxValue = parseInt(this.selectOptions.last().val());
	};

	this.createSlider = function ()
	{
		this.slider = $('<div id="' + this.selectProperties.id + '">')
				.addClass('noUi-control')
				.addClass(this.selectProperties.dataClass)
				.attr('data-for', this.select.attr('id'))
				.attr('data-type', this.type);
		this.initSliderOptions();
	};

	this.initSliderOptions = function ()
	{
		this.sliderOptions.direction = (Metronic.isRTL() ? "rtl" : "ltr");
		this.sliderOptions.step = 1;
		switch (this.type) {
			case this.SLIDER:
				this.sliderOptions.connect = 'lower';
				break;
			case this.RANGER:
				this.sliderOptions.handles = 2;
				this.sliderOptions.connect = true;
				break;
		}
	};

	this.setSliderRange = function (min, max)
	{
		this.sliderOptions.range = {
			'min': min,
			'max': max
		};
	};

	this.setSliderStart = function (start, end)
	{
		var startValue = parseInt(start);
		var endValue = parseInt(end);
		switch (this.type) {
			case this.SLIDER:
				this.sliderOptions.start = startValue;
				this.selectSliderValue(this.sliderOptions.start);
				break;
			case this.RANGER:
				this.sliderOptions.start = [startValue, endValue];
				this.selectRangeValue(this.sliderOptions.start);
				break;
		}
		this.saveActualValue(this.sliderOptions.start);
	};

	this.getStartOptionValue = function ()
	{
		var value = this.selectOptions.filter(':selected').first().val();
		return value ? value : this.selectMinValue;
	};

	this.getEndOptionValue = function ()
	{
		var value = this.selectOptions.filter(':selected').last().val();
		return value ? value : this.selectMaxValue;
	};

	this.getSelectedOptionFromValue = function (value)
	{
		return this.selectOptions.filter(function () {
			return parseInt($(this).val()) === parseInt(value);
		});
	};

	this.getSelectedOptionFromValues = function (values)
	{
		var firstValue = parseInt(values[0]);
		var secondValue = parseInt(values[1]);
		return this.selectOptions.filter(function () {
			var itemValue = parseInt($(this).val());
			return itemValue === firstValue || itemValue === secondValue;
		});
	};

	this.getSlider = function ()
	{
		return this.slider ? this.slider : $('#' + this.selectProperties.id);
	};

	this.deselectAllOptions = function ()
	{
		this.selectOptions.each(function (key, value) {
			$(value).prop('selected', false);
		});
	};

	this.getPipsValuesFromOptionObject = function ()
	{
		return Object.keys(this.selectOptionsObject).map(function (val) {
			return parseInt(val);
		});
	};

	this.selectSliderValue = function (value)
	{
		if (this.type === this.SLIDER) {
			var selectedOption = this.getSelectedOptionFromValue(value);
			selectedOption.prop('selected', true);
			if (this.selectProperties.isTooltipFixed) {
				var fixedTooltip = $('#' + this.selectProperties.id + '-tooltip');
				fixedTooltip.text(selectedOption.text());
			}
		}
	};

	this.selectRangeValue = function (value)
	{
		if (this.type === this.RANGER) {
			var selectedOptions = this.getSelectedOptionFromValues(value);
			this.deselectAllOptions();
			selectedOptions.each(function (key, value) {
				$(value).prop('selected', true);
			});
			this.getSlider().val(value);
		}
	};

	this.saveActualValue = function (value)
	{
		this.getSlider().attr('data-actual-value', JSON.stringify(value));
	};

	this.getPreviousValue = function ()
	{
		var previousValue = this.getSlider().attr('data-actual-value');
		return typeof previousValue === 'undefined' ? null : JSON.parse(previousValue);
	};

	this.recognizeWhichHandleMoved = function (value)
	{
		switch (this.type) {
			case this.RANGER:
				var lowerPrevValue = parseInt(this.getPreviousValue()[0]);
				var upperPrevValue = parseInt(this.getPreviousValue()[1]);
				var lowerActlValue = parseInt(value[0]);
				var upperActlValue = parseInt(value[1]);
				if (lowerPrevValue !== lowerActlValue) {
					return this.LOWER;
				}
				if (upperPrevValue !== upperActlValue) {
					return this.UPPER;
				}
				break;
			default:
				return this.LOWER;
		}
	};

	// extends
	this.extendTooltips = function ()
	{
		if (this.selectProperties.isTooltip) {
			this.slider.addClass('hasTooltip');
			var instance = this;
			this.slider.Link('lower').to('-inline-<div class="noUi-tooltip"></div>', function (value) {
				var selectedOption = instance.getSelectedOptionFromValue(value);
				$(this).html('<span>' + selectedOption.text() + '</span>');
			});
			if (this.type === this.RANGER) {
				this.slider.Link('upper').to('-inline-<div class="noUi-tooltip"></div>', function (value) {
					var selectedOption = instance.getSelectedOptionFromValue(value);
					$(this).html('<span>' + selectedOption.text() + '</span>');
				});
			}
		}

		if (this.selectProperties.isTooltipFixed) {
			this.slider.addClass('hasTooltipFixed');
			this.fixedTooltip = $('<div id="' + this.selectProperties.id + '-tooltip">')
					.addClass('noUi-tooltip-fixed');
			var selectedOption = this.getSelectedOptionFromValue(this.select.val());
			this.fixedTooltip.text(selectedOption.text());
			this.select.after(this.fixedTooltip);
		}
	};

	this.extendPips = function ()
	{
		if (this.selectProperties.isPips) {
			this.slider.addClass('hasPips');

			var instance = this;
			this.slider.noUiSlider_pips({
				mode: 'values',
				values: this.getPipsValuesFromOptionObject(),
				density: 1,
				stepped: false,
				format: {
					to: function (value) {
						return instance.selectOptionsObject[value] || value;
					}
				}
			});
		}
	}

	// events
	this.sliderOnSlide = function (e, value)
	{
		var selectId = $(this).attr('data-for');
		var type = $(this).attr('data-type');
		var sliderInstance = new NoUiSlider($('#' + selectId));
		sliderInstance.setSelect();
		sliderInstance.setType(type);
		sliderInstance.selectSliderValue(value);
		sliderInstance.selectRangeValue(value);
	};

	this.sliderOnChange = function (e, value)
	{
		var selectId = $(this).attr('data-for');
		var type = $(this).attr('data-type');
		var sliderInstance = new NoUiSlider($('#' + selectId));
		sliderInstance.setSelect();
		sliderInstance.setType(type);
		if (sliderInstance.selectProperties.isExtendEmptyValue) {
			var lowerValue = parseInt(value[0]);
			var upperValue = parseInt(value[1]);
			var emptyValue = sliderInstance.selectProperties.extendEmptyValue;
			var replaceValue = sliderInstance.selectProperties.extendEmptyReplace;
			
			switch (sliderInstance.recognizeWhichHandleMoved(value)) {
				case sliderInstance.LOWER:
					if (lowerValue === emptyValue) {
						upperValue = emptyValue;
					}
					break;
				case sliderInstance.UPPER:
					if (lowerValue === emptyValue) {
						lowerValue = replaceValue;
					}
					break;
			}
			value = [lowerValue, upperValue];
			sliderInstance.selectRangeValue(value);
		}
		sliderInstance.saveActualValue(value);
	};

};

NoUiSlider.prototype.buildSlider = function ()
{
	this.build(this.SLIDER);
};

NoUiSlider.prototype.buildRanger = function ()
{
	this.build(this.RANGER);
};