jQuery(document).ready(function() {
	var field = new Translations(jQuery('.translations'));
	
	jQuery('.translations .context .reference_value').click(function(event){
		event.preventDefault();
		
		$target = jQuery(event.currentTarget);
		$target.parent().find('textarea:eq(0)').toggle('fast', function(){
			$target.find('.fl_plus, .fl_minus').toggle();
		});
	});
	jQuery('.translations .context textarea[name=\'reference_value\'], .fl_minus').hide();
});

function Translations(field) {
	this.field = field;
	
	this.init();
}

Translations.prototype.init = function() {
	var self = this,
		activeTab = this.field.find('ul.tabs li.active');
	
	// Fallback to first tab if no tab is set as active by default
	if (activeTab.length == 0) {
		activeTab = this.field.find('ul.tabs li:eq(0)');
	}
	
	// bind tab events
	this.field.find('ul.tabs li').bind('click', function(e) {
		e.preventDefault();
		self.setActiveTab(jQuery(this).attr('class').split(' ')[0]);
	});
	
	// Show the active tab
	this.setActiveTab(activeTab.attr('class').split(' ')[0]);
}

Translations.prototype.setActiveTab = function(tab_name) {
	var self = this;
	
	// hide all tab panels
	this.field.find('.tab-panel').hide();
	
	// find the desired tab and activate the tab and its panel
	this.field.find('ul.tabs li').each(function() {
		var tab = jQuery(this);

		if (tab.hasClass(tab_name)) {
			tab.addClass('active');
			
			var panel = tab.parent().parent().find('.tab-' + tab_name);
			panel.show();

		} else {
			tab.removeClass('active');
		}
	});
}

/*---------------------------------------------------------------------------*/