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
                    primary: "dashicons dashicons-no-alt"
                }
            },
            {
                text: "Abstenerme",
                click: function () {

                },
                class: "vote-button vote-abstain",
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

            var data = [
                {
                    value: 50,
                    color: "rgb(118, 191, 49)",
                    highlight: "rgb(86, 165, 11)",
                    label: "A favor"
                },
                {
                    value: 300,
                    color:"rgb(203, 35, 35)",
                    highlight: "rgb(204, 0, 0)",
                    label: "En contra"
                },
                {
                    value: 100,
                    color: "#666",
                    highlight: "#444",
                    label: "Abstenciones"
                }
            ];

            var context = jQuery("#voting_chart").get(0).getContext("2d");
            var myPieChart = new Chart(context).Pie(data);
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
