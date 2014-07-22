jQuery(document).ready(function() {
    var detail = jQuery("#vote-detail"),
        row = is_admin == "yes" ? jQuery('.row-title') : jQuery('tr.type-vote'),
        buttons_three_actions = [
            {
                text: "A favor",
                click: function () {
                    jQuery.post(ajaxurl, {
                        action: 'submit_vote',
                        vote_id: jQuery(this).attr('data-vote'),
                        vote_decision: "for"
                    }, function(response) {
                        detail.dialog('option', 'buttons', button_disabled);
                    });
                },
                class: "vote-button vote-yes",
                icons: {
                    primary: "dashicons dashicons-yes"
                }
            },
            {
                text: "En contra",
                click: function () {
                    jQuery.post(ajaxurl, {
                        action: 'submit_vote',
                        vote_id: jQuery(this).attr('data-vote'),
                        vote_decision: 'against'
                    }, function(response) {
                        detail.dialog('option', 'buttons', button_disabled);
                    });
                },
                class: "vote-button vote-no",
                icons: {
                    primary: "dashicons dashicons-no-alt"
                }
            },
            {
                text: "Abstenerme",
                click: function () {
                    jQuery.post(ajaxurl, {
                        action: 'submit_vote',
                        vote_id: jQuery(this).attr('data-vote'),
                        vote_decision: 'abstain'
                    }, function(response) {
                        detail.dialog('option', 'buttons', button_disabled);
                    });
                },
                class: "vote-button vote-abstain",
            }
        ],
        buttons_submit = [
            {
                text: "Votar",
                click: function () {
                    jQuery.post(ajaxurl, {
                        action: 'submit_vote',
                        vote_id: jQuery(this).attr('data-vote'),
                        vote_decision: "submit"
                    }, function(response) {
                        detail.dialog('option', 'buttons', button_disabled);
                    });
                },
                class: "vote-button vote-yes",
                icons: {
                    primary: "dashicons dashicons-yes"
                }
            }
        ],
        button_disabled = [
            {
                text: "Ya has votado",
                disabled: true
            }
        ];

    row.click(function(event) {
        event.preventDefault();

        var clicked_element = jQuery(this),
            post_id = is_admin == "yes" ? jQuery(this).attr('href').match(/post=(\d+)/)[1] : jQuery(this).attr('id').match(/post-(\d+)/)[1];

        jQuery.post(ajaxurl, {
            action: "get_vote_status",
            vote_id: post_id
        }, function (response) {
            clicked_element.addClass(response);
        });

        var has_voted = clicked_element.hasClass('has_voted'),
            is_poll   = jQuery('#vote-is-poll').val() == "true" ? true : false,
            buttons_actions = is_poll ? buttons_submit : buttons_three_actions,
            buttons_available = has_voted ? button_disabled : buttons_actions;

        detail.dialog({
            'dialogClass'   : 'wp-dialog',
            'modal'         : true,
            'autoOpen'      : false,
            'buttons'       : buttons_available,
            'closeOnEscape' : true,
            'title'         : "Detalle de la propuesta",
            'height'        : 600,
            'width'         : 900,
            'position'      : { my: "top", at: "top", of: jQuery("#wpwrap") }
        });

        detail.dialog('open');

        moment.lang('es');

        jQuery.post(ajaxurl, {
            action: 'show_vote',
            post_id  : post_id,
            countdown: moment("201407222359", "YYYYMMDDHHii").fromNow()
        }, function (response) {
            jQuery("#vote-detail").html(response);

            var data = [
                {
                    value: 300,
                    color: "rgb(118, 191, 49)",
                    highlight: "rgb(86, 165, 11)",
                    label: "A favor"
                },
                {
                    value: 50,
                    color:"rgb(203, 35, 35)",
                    highlight: "rgb(204, 0, 0)",
                    label: "En contra"
                },
                {
                    value: 10,
                    color: "#666",
                    highlight: "#444",
                    label: "Abstenciones"
                }
            ],
            options = {
                percentageInnerCutout: 50
            };

            var context = jQuery("#voting_chart").get(0).getContext("2d");
            var chart = new Chart(context).Pie(data, options);
        });
    });
});
