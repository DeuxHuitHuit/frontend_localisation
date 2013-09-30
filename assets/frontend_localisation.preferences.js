(function ($, undefined) {

	$(document).ready(function () {

		var $form = $('#contents').find('form');
		var langs_name = "settings[frontend_localisation][langs]";

		$form.on('submit', function () {
			var langs_val = $('select[name="' + langs_name + '"]').val();

			$form.append($('<input/>').attr({
				'name' : langs_name,
				'type' : 'hidden',
				'value': langs_val.join(',')
			}));
		});

	});

})(jQuery);
