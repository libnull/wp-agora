jQuery(document).ready(function(){
    var info = jQuery("#modal-content");
    info.dialog({
        'dialogClass'   : 'wp-dialog',
        'modal'         : true,
        'autoOpen'      : false,
        'closeOnEscape' : true,
        'buttons'       : {
            "Close": function() {
                jQuery(this).dialog('close');
            }
        }
    });
    jQuery("#open-modal").click(function(event) {
        event.defaultPrevented;
        info.dialog('open');
    });
});
