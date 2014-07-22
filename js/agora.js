jQuery(document).ready(function() {
    var detail = jQuery("#vote-detail");

    detail.dialog({
        'dialogClass'   : 'wp-dialog',
        'modal'         : true,
        'autoOpen'      : false,
        'closeOnEscape' : true,
        'buttons'       : [
            {
                text: "A favor",
                click: function () {

                },
                class: "vote-button vote-yes",
                icons: {
                    primary: "dashicons dashicons-yes"
                }
            },
            {
                text: "En contra",
                click: function () {

                },
                class: "vote-button vote-no",
                icons: {
                    primary: "dashicons dashicons-no"
                }
            },
            {
                text: "Me abstengo",
                click: function () {

                },
                class: "vote-button vote-abstain",
                icons: {
                    primary: "dashicons dashicons-minus"
                }

            }
        ],
        'title'         : "Detalle de la propuesta",
        'height'        : 600,
        'width'         : 900,
        'position'      : { my: "top", at: "top", of: jQuery("#wpwrap") }
    });

    var row = is_admin == "yes" ? jQuery('.row-title') : jQuery('tr.type-vote');

    row.click(function(event) {
        event.preventDefault();
        detail.dialog('open');

        var post_id = is_admin == "yes" ? jQuery(this).attr('href').match(/post=(\d+)/)[1] : jQuery(this).attr('id').match(/post-(\d+)/)[1];

        jQuery.post(ajaxurl, {
            action: 'show_vote',
            post_id  : post_id,
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
