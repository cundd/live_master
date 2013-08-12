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

	jQuery(function () {
		var configurationForm = jQuery('#configuration');

		$('.configuration-data input[data-changed=0]')
		.on('change',
			function () {
				$(this).attr("data-changed", 1);
			})
		.on('keyup',
			function (event) {
				if (event.keyCode === 13) {
					configurationForm.submit();
				}

			})
		;
		configurationForm.submit(function () {
			var	form = $(this), previousData,
				configurationData = $('.configuration-data input[data-changed=1]').serializeObject(),
				hiddenConfigurationDataField = form.find('[name="tx_livemaster_livemaster[configuration][data]"]'),
				url = form.attr('action');

			previousData = JSON.parse(hiddenConfigurationDataField.val());
			configurationData = $('.configuration-data input[data-changed=1]').serializeObject();
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
		})
	});
}(jQuery));