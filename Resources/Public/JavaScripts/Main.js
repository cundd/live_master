(function ($) {
	jQuery.fn.serializeObject = function() {
		var _name, dataArray = this.serializeArray(), dataObject = {};

		$.each(dataArray, function() {
			_name = this.name;
			if (dataObject[_name] !== undefined) {
				if (!dataObject[_name].push) {
					dataObject[_name] = [dataObject[_name]];
				}
				dataObject[_name].push(this.value || '');
			} else {
				dataObject[_name] = this.value || '';
			}
		});
		return dataObject;
	};

	var ConfigurationForm = function () {
		var _configurationForm = this.configurationForm = jQuery('#configuration'),
			_configurationFields = this.configurationFields = jQuery('.configuration-data input[data-changed]'),
			_this = this
			;

		this.resetFocusedField();

		_configurationForm.submit(function () {
			return _this.submit();
		});

		_configurationFields
			.on('change',
			function () {
				_this.configurationFieldChanged($(this));
			})
			.on('keydown', function (event) {
				_this.configurationFieldKeyPress(event, this);
			})
		;

		// _configurationFields.parent().append(jQuery('.configuration-data input[data-changed]').first().clone());
	};


	ConfigurationForm.prototype = {
		Constructor: ConfigurationForm,

		submit: function () {
			var	form = this.configurationForm, previousData, configurationData,
				hiddenConfigurationDataField = form.find('[name="tx_livemaster_livemaster[configuration][data]"]'),
				url = form.attr('action');

			this.configurationFields.filter(':focus').blur();

			configurationData = this.configurationFields.filter('[data-changed="1"]').serializeObject();

			previousData = JSON.parse(hiddenConfigurationDataField.val());
			configurationData = $.extend({}, previousData, configurationData);

			// configuration['tx_livemaster_livemaster[data]'] = JSON.stringify(configuration.data);
			// configuration.data = undefined;

			if (jQuery.isEmptyObject(configurationData)) {
				return false;
			}
			hiddenConfigurationDataField.val(JSON.stringify(configurationData));

			return true;
			if (url) {
				$.ajax({
					type: "POST",
					url: url,
					data: form.serializeArray(),
					dataType: 'text',
					cache: false,
					success: function (data, textStatus, jqXHR) {
						window.location.reload();
						console.log(textStatus, jqXHR);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						window.location.reload();
						console.error(jqXHR, textStatus, errorThrown);
					}

				});
			}
			return false;
		},

		configurationFieldChanged: function (field) {
			field.attr('data-changed', 1);
		},

		configurationFieldKeyPress: function (event, element) {
			var $element = $(element), type = $element.attr('type'), result = false;
			switch(event.keyCode) {
				case 13: // Enter
					this.saveFocusedField($element);
					this.configurationForm.submit();
					break;

				case 38: // Up
					if (type === 'text' && this.changeFieldNumberValue($element)) {
						$element.change();
						result = true;
					}
					break;

				case 40: // Down
					if (type === 'text' && this.changeFieldNumberValue($element, true)) {
						$element.change();
						result = true;
					}
					break;
			}
			return result;
		},

		saveFocusedField: function (focusedField) {
			localStorage['Cundd.LiveMaster.ConfigurationForm.focusedField'] = focusedField.attr('id');
		},

		resetFocusedField: function () {
			if (localStorage['Cundd.LiveMaster.ConfigurationForm.focusedField']) {
				$('#' + localStorage['Cundd.LiveMaster.ConfigurationForm.focusedField']).focus();
			}
		},

		changeFieldNumberValue: function (field, decreaseInsteadOfIncrease) {
			var value = field.val(), parts, numberValue;
			if (arguments.length < 2) {
				decreaseInsteadOfIncrease = false;
			}
			parts = value.match(/^(\d+(?:\.\d+)?)(.*)$/);
			if (parts) {
				numberValue = parseInt(parts[1]);
				if (!decreaseInsteadOfIncrease) {
					numberValue++;
				} else {
					numberValue--;
				}
				field.val(numberValue + parts[2]);
				return true;
			}
			return false;
		}
	};

	window.ConfigurationForm = ConfigurationForm;
}(jQuery));