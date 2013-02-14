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
        
        $('.Tadd').click(function(event){
            event.preventDefault();
            
            name = $(this).attr('name');
            //escape all the square brackets and slashes
            name = name.replace(/\[/gi,'\\[');
            name = name.replace(/\]/gi,'\\]');
            name = name.replace(/\//gi,'\\/');
            //the selector variable makes it simpler to understand when debugging
            selector = "button[name='"+name+"']";
            
            //for each handle with the same delete translation action tag
            $(selector).parent().each(function(){
                //clone the previous item
                $item = $(this).prev().clone(true);
                //getNewNameTag (JQObject, XMLElem, keep, context)
                var new_fieldtag = getNewNameTag($item , 'input', 3, false);
                var new_buttontag = getNewNameTag($item,'button',2, false);
                
                //replace all the values and names of fields and buttons
                $item.find('input').each(function(){
                    $(this).attr('value', '');
                    $(this).attr('name','fields'+new_fieldtag+'[handle]');
                });
                $item.find('textarea').each(function(){
                    $(this).text('');
                    $(this).attr('name','fields'+new_fieldtag+'[value]');
                });
                $item.find('button').each(function(){
                    $(this).attr('name','action'+new_buttontag);
                });
                
                //paste the modified itemp before the add translation button
                $(this).before($item);
            });
            count++;
        });
        
        $('.Tdel>button').click(function(event){
            event.preventDefault();
            //escape all the square brackets and slashes
            name = $(this).attr('name');
            name = name.replace(/\[/gi,'\\[');
            name = name.replace(/\]/gi,'\\]');
            name = name.replace(/\//gi,'\\/');
            
            selector = "button[name='"+name+"']";
            //get the buttons related item and remove it
            $(selector).parent().parent().each(function(){
            
                $(this).remove();
                
            });
        });
        
        $('.Cdel').click(function(event){
            event.preventDefault();
            
            name = $(this).attr('name');
            //escape all the square brackets and slashes
            name = name.replace(/\[/gi,'\\[');
            name = name.replace(/\]/gi,'\\]');
            name = name.replace(/\//gi,'\\/');
            
            selector = "button[name='"+name+"']";
            //get the buttons related context and delete it
            $(selector).parent().parent().each(function(){
            
                $(this).remove();
                
            });
        });
        
        $('.Cadd').click(function(event){
            event.preventDefault();
            //For each tab panel
            $('.tab-panel').each(function(){
            
                //clone the first context
                $context = $(this).children('.context').first().clone(true);
                //remove all but one translation item
                $context.children('div').not(':first').not('.controler').remove();
                
                //get new fieldnames
                var new_fieldname = getNewNameTag($context , 'input', 2, true);
                var new_context = getNewNameTag($context, 'input', 2, true); 
                
                //replace all the values and names of fields and buttons
                $context.find('input').not(':first').each(function(){
                    $(this).attr('name','fields'+new_fieldname+'[magichandle][handle]');
                });
                $context.find('input:first').each(function(){
                    $(this).attr('name','contexts'+new_context);
                });
                $context.find('input').each(function(){
                    $(this).attr('value', '');
                });
                $context.find('textarea').each(function(){
                    $(this).text('');
                    $(this).attr('name','fields'+new_fieldname+'[magichandle][value]');
                });
                $context.find('button').each(function(){
                    new_buttonname = getNewNameTag($(this),'self',1, true);
                    $(this).attr('name','action'+new_buttonname);
                });
                // add the item handle fo the translation delete button
                $context.find('.Tdel button').each(function(){
                    new_buttonname = getNewNameTag($(this),'self',1, true);
                    $(this).attr('name','action'+new_buttonname+'[magichandle]');
                });
                // append the modified context!
                $(this).append($context);
            });
            count++;
        });
	});
})(this.jQuery);


var count=1;

function getNewNameTag(JQObject, XMLElem, keep, context){
    var $ = jQuery;
    var result;
    
    XMLElem == 'self' ? true : JQObject = JQObject.find(XMLElem) ;
    JQObject.each(function(){
    
        var fieldname = $(this).attr('name');
        var tags = fieldname;
        var nametag = '';
        //no spaces in context with this regexp!
        tagarray = tags.match(/\[[a-z0-9\-\_\/]+\]/gi);
        
        for(i=0;i<keep;i++){
            nametag += tagarray[i];
        }
        context ?  handle = '[/new-'+count+']' : handle = '[new-'+count+']';
        
        result = nametag+handle;
        
    });
    return result;
}
