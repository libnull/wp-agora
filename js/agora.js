jQuery(document).ready(function() {
    var detail = jQuery("#vote-detail"),
        row = is_admin == "yes" ? jQuery('.row-title') : jQuery('.vote-row');

    row.click(function(event) {
        event.preventDefault();

        var clicked_element = jQuery(this),
            post_id = is_admin == "yes" ? jQuery(this).attr('href').match(/post=(\d+)/)[1] : jQuery(this).attr('id').match(/post-(\d+)/)[1];

        jQuery.post(ajaxurl, {
            action: "get_vote_status",
            vote_id: post_id
        }, function (response) {
            var status = JSON.parse(response);

            var buttons_three_actions = [
                {
                    text: "A favor",
                    click: function () {
                        jQuery.post(ajaxurl, {
                            action: 'submit_vote',
                            vote_id: post_id,
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
                            vote_id: post_id,
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
                            vote_id: post_id,
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
                            vote_id: post_id,
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
            button_already_voted = [
                {
                    text: "Ya has votado",
                    disabled: true
                }
            ],
            button_not_allowed = [
                {
                    text: "No puedes votar",
                    disabled: true
                }
            ];

            if (status.is_allowed && !status.has_ended) {
                var buttons_actions   = status.is_poll ? buttons_submit : buttons_three_actions,
                    buttons_available = status.has_voted ? button_disabled : buttons_actions;
            } else {
                var buttons_available = button_not_allowed;
            }

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
                countdown: moment(status.deadline, "YYYY-MM-DD HH:ii:ss").fromNow()
            }, function (response) {
                jQuery("#vote-detail").html(response);

                if (jQuery("#voting_chart").length > 0) {
                    var data = [
                        {
                            value: status.count_for,
                            color: "rgb(118, 191, 49)",
                            highlight: "rgb(86, 165, 11)",
                            label: "A favor"
                        },
                        {
                            value: status.count_against,
                            color:"rgb(203, 35, 35)",
                            highlight: "rgb(204, 0, 0)",
                            label: "En contra"
                        },
                        {
                            value: status.count_abstain,
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
                }
            });
        });

    });
});
