jQuery(document).ready(function() {
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

            jQuery("#vote-chart").highcharts({
                chart: {
                    animation: false,
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false
                },
                title: {
                    text: ''
                },
                subTitle: {
                    text: ''
                },
                tooltip: {
                    enabled: false
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: false
                        },
                        showInLegend: false
                    }
                },
                credits: {
                    enabled: false
                },
                series: [{
                    type: 'pie',
                    data: [
                        ['Firefox',   45.0],
                    ]
                }]
            });

        });
    });

    jQuery("#vote-detail").on("click", '.vote-action', function(event) {
        event.preventDefault();

        jQuery.post(ajaxurl, {
            action: 'submit_vote',
            vote_id: jQuery(this).attr('data-vote'),
            vote_decision: jQuery(this).attr('data-decision')
        }, function(response) {
            jQuery("#vote-action-buttons").html(response);
        });
    });

});
