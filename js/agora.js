jQuery(document).ready(function(){
    var detail = jQuery("#vote-detail");

    detail.dialog({
        'dialogClass'   : 'wp-dialog',
        'modal'         : true,
        'autoOpen'      : false,
        'closeOnEscape' : true,
        'buttons'       : {
            "Cerrar": function() {
                jQuery(this).html('');
                jQuery(this).dialog('close');
            }
        },
        'title'         : "Detalle de la propuesta",
        'height'        : 600,
        'width'         : 900,
        'position'      : { my: "top", at: "top", of: jQuery("#wpwrap") }
    });
    jQuery(".row-title").click(function(event) {
        event.preventDefault();
        detail.dialog('open');

        jQuery.post(ajaxurl, {
            action: 'show_vote',
            href  : jQuery(this).attr('href'),
            countdown: moment("20140820", "YYYYMMDD").countdown().toString()
        }, function (response) {
            jQuery("#vote-detail").html(response);
        });
    });

});
