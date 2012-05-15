(function($, undefined){

	$(document).ready(function(){
		var $m_fields = $('.field-multilingual');
		if( $m_fields.length === 0 ) return;

		var $m_ul = $m_fields.find('ul.tabs');
		if( $m_ul.length === 0 ) return;

		var $m_tabs = $m_ul.find('li');
		if( $m_tabs.length === 0 ) return;

		var $m_panels = $m_fields.find('.tab-element, .tab-panel');
		if( $m_panels.length === 0 ) return;

		$m_ul.on('click', 'li', function(){
			var lang_code = $(this).data('lang_code');

			$m_tabs
				.each(function(){
					$(this).removeClass('active');
				})
				.filter('.'+lang_code).each(function(){
					$(this).addClass('active');
				});

			$m_panels.hide().filter('.tab-'+lang_code).show();
		});

		$m_tabs
			.each(function(){
				$(this).data('lang_code', $(this).attr('class'));
			})
			.eq(0).click();
	});

}(this.jQuery));
