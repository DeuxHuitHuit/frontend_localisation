(function($, Symphony, window, undefined){

	function init(){
		$('.field-multilingual').each(function(){
			new MultilingualTabs( $(this) );
		});
	}

	$(document).ready(function(){
		var base = Symphony.Context.get('root')+'/extensions/frontend_localisation/assets';

		if( typeof this.MultilingualTabs !== 'function' ){
			$.getScript(base+'/frontend_localisation.multilingual_tabs.js', init);
		}
		else{
			init();
		}
	});

}(this.jQuery, this.Symphony, this));
