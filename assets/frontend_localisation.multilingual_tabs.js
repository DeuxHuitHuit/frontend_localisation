(function($, Symphony, window, undefined){

	function MultilingualTabs(field){
		this.field = field;
		this.init();
	}

	MultilingualTabs.prototype = {
		init: function(){
			var self = this,
				activeTab = this.field.find('ul.tabs li.active');

			// Fallback to first tab if no tab is set as active by default
			if( activeTab.length === 0 ){
				activeTab = this.field.find('ul.tabs li:eq(0)');
			}

			// bind tab events
			this.field.find('ul.tabs li').bind('click', function(e){
				e.preventDefault();
				self.setActiveTab($(this).attr('class').split(' ')[0]);
			});

			// Show the active tab
			this.setActiveTab(activeTab.attr('class').split(' ')[0]);
		},

		setActiveTab: function(tab_name){
			// hide all tab panels
			this.field.find('.tab-panel').hide();

			// find the desired tab and activate the tab and its panel
			this.field.find('ul.tabs li').each(function(){
				var tab = $(this);

				if( tab.hasClass(tab_name) ){
					tab.addClass('active');

					var panel = tab.parent().parent().find('.tab-'+tab_name);
					panel.show();

				}else{
					tab.removeClass('active');
				}
			});
		}
	};

	// export Constructor
	window.MultilingualTabs = MultilingualTabs;

}(this.jQuery, this.Symphony, this));
