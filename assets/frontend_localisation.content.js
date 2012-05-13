;(function($, undefined){

	$(document).ready(function(){
		$('.context .reference_value').click(function(event){
			event.preventDefault();

			$target = $(event.currentTarget);
			$target.parent().find('textarea:eq(0)').toggle('fast', function(){
				$target.find('.fl_plus, .fl_minus').toggle();
			});
		});

		$(".context textarea[name='reference_value'], .fl_minus").hide();
	});

})(this.jQuery);
