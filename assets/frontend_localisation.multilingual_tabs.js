(function($, undefined){

	var collection = {
		$m_tabs: $(),
		$m_panels: $()
	};

	$.fn.symphonyMultilingualTabs = function(){

		return $(this)
			.filter(function(){
				return $(this).hasClass('multilingual-initialised') !== true;
			})
			.each(function(){

				var $this = $(this).addClass('multilingual-initialised');

				// safe checks
				var $m_ul = $this.find('ul.tabs');
				if( $m_ul.length === 0 ) return $this;

				var $m_tabs = $m_ul.find('li');
				if( $m_tabs.length === 0 ) return $this;

				var $m_panels = $this.find('.tab-element, .tab-panel');
				if( $m_panels.length === 0 ) return $this;

				// add language information
				$m_tabs.each(function(){
					$(this).data('lang_code', $(this).attr('class'));
				});

				// current tab which will be clicked
				var $current_tab = '';

				if( collection.$m_tabs.length === 0 ){
					$current_tab = $m_tabs.eq(0);
				}
				else{
					$current_tab = collection.$m_tabs.filter('.active:eq(0)');
				}

				// store new elements
				collection.$m_tabs = collection.$m_tabs.add($m_tabs);
				collection.$m_panels = collection.$m_panels.add($m_panels);

				// bind events
				$m_ul.on('click', 'li', function(){
					var lang_code = $(this).data('lang_code');

					collection.$m_tabs.removeClass('active').filter('.'+lang_code).addClass('active');
					collection.$m_panels.hide().filter('.tab-'+lang_code).show();
				});

				// trigger current tab
				$current_tab.click();

			});

	};

})(this.jQuery);
