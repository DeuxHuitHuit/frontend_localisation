/**
 * This is taken from Quick DS exension by Nathan Martin.
 * 
 * Thank you for this!
 */

jQuery(document).ready(function() {
	
	if(Symphony.Language.add) {
		Symphony.Language.add({
			'Collapse' : false,
			'Expand All' : false,
			'Collapse All' : false
		});
	}
	
	// Only run if no errors were returned
	if(jQuery('.error').length > 0) return;
	
	// Adjust the bottom margin to fix layout
	jQuery('fieldset.settings legend').css('margin-bottom', '0');

	// Add some quick & dirty hover styling so the user
	// has a better idea they can click the fieldset to expand
	// (no need to put lots of 'expand' buttons everywhere)
	jQuery('fieldset').hover(
		function() { if(jQuery(this).find('.group').is(':hidden')) { jQuery(this).css('background-color', '#f5f5f5'); } },
		function() { if(jQuery(this).find('.group').is(':hidden')) { jQuery(this).css('background-color', 'inherit'); } }
	);
	
	// Add the hand cursor to the legend
	jQuery("fieldset.settings legend").css('cursor', 'pointer');
	
	// Handle the actual toggled expand/collapse
	jQuery("fieldset.settings legend").toggle(
		function() {
			
			var l_self = jQuery(this);
			var l_target = jQuery(this).parent();
			
			// Hide the group
			l_self.next().hide();
			
			// Remove the 'hide' button
			l_self.find('a').remove();
			
			// Add the click handler to the entire fieldset
			l_target.click(function() { l_self.click(); });
			l_target.css('cursor', 'pointer');
		},
		function() {
			
			var l_self = jQuery(this);
			var l_target = jQuery(this).parent();
			
			// Show the group
			l_self.next().show();
			
			// Add the 'hide' button
			var l_label = 'Collapse';
			if(Symphony.Language.get) l_label = Symphony.Language.get(l_label);
			l_self.append(' <a title="Toggle collapse" class="togglecollapse">(' + l_label + ')</a>');
			
			// Remove the click handler from the fieldset
			l_target.unbind('click');
			l_target.css('cursor', 'auto');
			
			// Remove the hover styling
			l_target.css('background-color', 'inherit');
		}
	);
	
	// Add the 'show/hide all' button
	var l_label = 'Expand All';
	if(Symphony.Language.get) l_label = Symphony.Language.get(l_label);
	jQuery('form h2').append(' - (<a title="Toggle all" class="toggleall">' + l_label + '</a>)');
	
	// Handle the actual 'show/hide all' toggle
	jQuery('a.toggleall').toggle(
		function() {
			
			// Expand only those items that are already hidden
			jQuery("fieldset.settings").each(function() {
				if(jQuery(this).find('.group').is(':hidden')) {
					jQuery(this).find('legend').click();
				}
			});
			
			// Switch button text
			var l_label = 'Collapse All';
			if(Symphony.Language.get) l_label = Symphony.Language.get(l_label);
			jQuery('a.toggleall').text(l_label);
		},
		function() {
			
			// Collapse only those items that are already expanded
			jQuery("fieldset.settings").each(function() {
				if(jQuery(this).find('.group').is(':visible')) {
					jQuery(this).find('legend').click();
				}
			});
			
			// Switch button text
			var l_label = 'Expand All';
			if(Symphony.Language.get) l_label = Symphony.Language.get(l_label);
			jQuery('a.toggleall').text(l_label);
		}
	);
	
	// Force an initial collapse on all elements
	// (Doesn't matter if there is delay, this is just to simplify the UI)
	jQuery("fieldset.settings legend").click();

});