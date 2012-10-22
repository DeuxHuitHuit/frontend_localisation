jQuery(function($){
    fl_bind_tab();
    fl_bind_handle();
    // Bind delete:
    $('table.translations a.delete').click(function(e){
        e.preventDefault();
        $(this).parent().parent().remove(); // simple as that!
        fl_bind_tab();
        // When not a single translation is set, start with a blank entry:
        if($('table.translations tr.data').length == 0)
        {
            fl_clone_template();
        }
    });
    // When not a single translation is set, start with a blank entry:
    if($('table.translations tr.data').length == 0)
    {
        fl_clone_template();
    }
});

function fl_bind_tab()
{
    var $ = jQuery;
    // unbind the tab:
    $('table.translations input').unbind('keydown');
    $('table.translations input:last').keydown(function(e){
        if(e.keyCode == 9) {
            // Tab pressed, create new row
            e.preventDefault();
            fl_clone_template();
        }
    });
}

function fl_clone_template()
{
    var $ = jQuery;
    // Clone the template:
    $row = $('table.translations tr.template').clone(true);
    $row.removeClass('template').addClass('data');
    $('input[type=text]', $row).each(function(){
        this.name = this.name.replace('__HANDLE__', '');
    });
    $('table.translations').append($row);
    $('table.translations tr.data:last input').val('');
    $('table.translations tr.data:last input:first').focus();
    // rebind:
    fl_bind_tab();
    fl_bind_handle();
}

function fl_bind_handle()
{
    var $ = jQuery;
    // First unbind:
    $('table.translations input.handle').unbind('keyup');
    $('table.translations input.handle').keyup(function(e){
        $row = $(this).parent().parent();
        var handle = this.value;
        $('input[type=text]', $row).each(function(){
            // Replace the name:
            this.name = this.name.replace(/translations\[(.*)\]\[(.*)\]/, 'translations[\$1]['+handle+']');
        });
    });
}