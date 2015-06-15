// Timout handler on the indexing process
var timeoutHandler;
var timeoutHandlerIsCleared = false;

jQuery(document).ready(function () {
    jQuery(".radio_type").change(function () {

        if (jQuery("#self_host").attr("checked")) {
            jQuery('#div_self_hosted').slideDown("slow");
            jQuery('#hosted_on_other').css('display', 'none');
        }
        else if (jQuery("#other_host").attr("checked")) {
            jQuery('#hosted_on_other').slideDown("slow");
            jQuery('#div_self_hosted').css('display', 'none');


        }
    });

    // Clean the Solr index
    jQuery('#solr_delete_index').click(function (e) {

        jQuery('.status_del_message').addClass('loading');

        path = jQuery('#adm_path').val();

        jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            dataType: "json",
            data: {
                action: 'return_solr_delete_index'
            },
            timeout: 1000 * 3600 * 24,
            success: function (data) {

                // Errors
                if (data.status != 0 || data.message) {
                    jQuery('.status_index_message').html('<br><br>An error occured: <br><br>' + data.message);

                    // Block submit
                    alert('An error occured.');
                }
            },
            error: function (req, status, error) {
                var message = '';

                jQuery('.status_index_message').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + error + '<br><br>' + message);

            }
        });

    });

    // Stop the current Solr index process
    jQuery('#solr_stop_index_data').click(function () {

        jQuery('#solr_stop_index_data').attr('value', 'Stopping ... please wait');

        clearTimeout(timeoutHandler);

        timeoutHandlerIsCleared = true;
    });

    // Fill the Solr index
    jQuery('#solr_start_index_data').click(function () {

        jQuery('.status_index_icon').addClass('loading');

        jQuery('#solr_stop_index_data').css('visibility', 'visible');
        jQuery('#solr_start_index_data').hide();
        jQuery('#solr_delete_index').hide();

        batch_size = jQuery('#batch_size').val();
        is_debug_indexing = jQuery('#is_debug_indexing').prop('checked');

        err = 1;

        if (isNaN(batch_size) || (batch_size < 1)) {
            jQuery('.res_err').text("Please enter a number > 0");
            err = 0;
        }
        else {
            jQuery('.res_err').text();
        }

        if (err == 0) {
            return false;
        } else {

            call_solr_index_data(batch_size, 0, is_debug_indexing);

            // Block submit
            return false;
        }

    });


    // Promise to the Ajax call
    function call_solr_index_data(batch_size, nb_results, is_debug_indexing) {

        var nb_results_message = nb_results + ' documents indexed so far'

        jQuery('.status_index_message').html(nb_results_message);

        path = jQuery('#adm_path').val();

        return jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: {
                action: 'return_solr_index_data',
                batch_size: batch_size,
                nb_results: nb_results,
                is_debug_indexing: is_debug_indexing
            },
            dataType: "json",
            timeout: 1000 * 3600 * 24,

            success: function (data) {

                if (data.debug_text) {
                    // Debug
                    jQuery('.status_debug_message').append('<br><br>' + data.debug_text);

                    if (data.indexing_complete) {
                        // Freeze the screen to have time to read debug infos
                        return false;
                    }

                }

                if (data.status != 0 || data.message) {
                    // Errors
                    jQuery('.status_index_message').html('<br><br>An error occured: <br><br>' + data.message);

                }
                else if (!data.indexing_complete) {

                    // If indexing completed, stop. Else, call once more.
                    timeoutHandler = setTimeout(call_solr_index_data(batch_size, data.nb_results, is_debug_indexing), 100);


                } else {
                    jQuery('#solr_stop_index_data').click();

                }
            },
            error: function (req, status, error) {

                var message = '';

                if (batch_size > 100) {
                    message = '<br> You could try to decrease your batch size to prevent errors or timeouts.';
                }
                jQuery('.status_index_message').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + error + '<br><br>' + message);

            },
            timeout: function (req, status, error) {
                jQuery('.status_index_message').html('A timeout occured');

                //timeoutHandler = setTimeout(call_solr_index_data(batch_size, data.nb_results), 100);
            }
        });

    }


    jQuery('#save_selected_index_options_form').click(function () {
        ps_types = '';
        tax = '';
        fields = '';
        jQuery("input:checkbox[name=post_tys]:checked").each(function () {
            ps_types += jQuery(this).val() + ',';
        });
        pt_tp = ps_types.substring(0, ps_types.length - 1);
        jQuery('#p_types').val(pt_tp);
        jQuery("input:checkbox[name=taxon]:checked").each(function () {
            tax += jQuery(this).val() + ',';
        });
        tx = tax.substring(0, tax.length - 1);
        jQuery('#tax_types').val(tx);
        jQuery("input:checkbox[name=cust_fields]:checked").each(function () {
            fields += jQuery(this).val() + ',';
        });
        fl = fields.substring(0, fields.length - 1);
        jQuery('#field_types').val(fl);


    });
    jQuery('#save_selected_options_form').click(function () {


        result = '';
        jQuery(".facet_selected").each(function () {
            result += jQuery(this).attr('id') + ",";
        });
        result = result.substring(0, result.length - 1);

        jQuery("#select_fac").val(result);
    })
    jQuery('#save_selected_res_options_form').click(function () {
        num_of_res = jQuery('#number_of_res').val();
        num_of_fac = jQuery('#number_of_fac').val();
        err = 1;
        if (isNaN(num_of_res)) {
            jQuery('.res_err').text("Please enter valid number of results");
            err = 0;
        }
        else if (num_of_res < 1 || num_of_res > 100) {
            jQuery('.res_err').text("Number of results must be between 1 and 100");
            err = 0;
        }
        else {
            jQuery('.res_err').text();
        }

        if (isNaN(num_of_fac)) {
            jQuery('.fac_err').text("Please enter valid number of facets");
            err = 0;
        }
        else if (num_of_fac < 1 || num_of_fac > 100) {
            jQuery('.fac_err').text("Number of facets must be between 1 and 100");
            err = 0;
        }
        else {
            jQuery('.fac_err').text();

        }
        if (err == 0)
            return false;
    })
    jQuery('#save_selected_extension_groups_form').click(function () {
        err = 1;
        if (err == 0)
            return false;
    })

    jQuery('#check_solr_status').click(function () {
        path = jQuery('#adm_path').val();

        host = jQuery('#solr_host').val();
        port = jQuery('#solr_port').val();
        spath = jQuery('#solr_path').val();
        protocol = jQuery('#solr_protocol').val();


        if (spath.substr(spath.length - 1, 1) == '/')
            spath = spath.substr(0, spath.length - 1);

        jQuery('#solr_path').val(spath);

        error = 0;
        if (host == '') {
            jQuery('.host_err').text('Please enter solr host');
            error = 1;
        }
        else {
            jQuery('.host_err').text('');
        }

        if (isNaN(port) || port.length < 2) {
            jQuery('.port_err').text('Please enter valid port');
            error = 1;
        }
        else
            jQuery('.port_err').text('');

        if (spath == '') {
            jQuery('.path_err').text('Please enter solr path');
            error = 1;
        }
        else
            jQuery('.path_err').text('');
        if (error == 1) {
            return false;

        }
        else {
            jQuery('.img-succ').css('display', 'none');
            jQuery('.img-err').css('display', 'none');
            jQuery('.img-load').css('display', 'inline');

            jQuery.ajax({
                url: path + 'admin-ajax.php',
                type: "post",
                timeout: 10000,
                data: {
                    action: 'return_solr_instance',
                    'sproto': protocol,
                    'shost': host,
                    'sport': port,
                    'spath': spath
                },
                success: function (data1) {

                    jQuery('.img-load').css('display', 'none');
                    if (data1 == 0) {
                        jQuery('.solr_error').html('');
                        jQuery('.img-succ').css('display', 'inline');
                        jQuery('#settings_conf_form').submit();
                    }
                    else if (data1 == 1)
                        jQuery('.solr_error').text('Error in detecting solr instance');
                    else
                        jQuery('.solr_error').html(data1);

                },
                error: function (req, status, error) {

                    jQuery('.img-load').css('display', 'none');

                    jQuery('.solr_error').text('Timeout: we had no response from your Solr server in less than 10 seconds. It\'s probably because port ' + port + ' is blocked. Please try another port, for instance 443, or contact your hosting provider to unblock port ' + port + '.');
                }

            });

        }

    })
    jQuery('#check_solr_status_third').click(function () {
        path = jQuery('#adm_path').val();
        host = jQuery('#gtsolr_host').val();
        port = jQuery('#gtsolr_port').val();
        spath = jQuery('#gtsolr_path').val();
        pwd = jQuery('#gtsolr_secret').val();
        user = jQuery('#gtsolr_key').val();
        protocol = jQuery('#gtsolr_protocol').val();


        if (spath.substr(spath.length - 1, 1) == '/')
            spath = spath.substr(0, spath.length - 1);
        jQuery('#gtsolr_path').val(spath);
        error = 0;
        if (host == '') {
            jQuery('.ghost_err').text('Please enter solr host');
            error = 1;
        }
        else {
            jQuery('.ghost_err').text('');
        }

        if (isNaN(port) || port.length < 2) {
            jQuery('.gport_err').text('Please enter valid port');
            error = 1;
        }
        else
            jQuery('.gport_err').text('');

        if (spath == '') {
            jQuery('.gpath_err').text('Please enter solr path');
            error = 1;
        }
        else
            jQuery('.gpath_err').text('');
        if (pwd == '') {
            jQuery('.gsec_err').text('Please enter solr secret');
            error = 1;
        }
        else
            jQuery('.gsec_err').text('');
        if (user == '') {
            jQuery('.gkey_err').text('Please enter solr key');
            error = 1;
        }
        else
            jQuery('.gkey_err').text('');
        if (error == 1)
            return false;
        else {
            jQuery('.img-succ').css('display', 'none');
            jQuery('.img-err').css('display', 'none');
            jQuery('.img-load').css('display', 'inline');
            jQuery.ajax({
                url: path + 'admin-ajax.php',
                type: "post",
                data: {
                    action: 'return_solr_instance',
                    'sproto': protocol,
                    'shost': host,
                    'sport': port,
                    'spath': spath,
                    'spwd': pwd,
                    'skey': user
                },
                timeout: 10000,
                success: function (data1) {

                    jQuery('.img-load').css('display', 'none');
                    if (data1 == 0) {
                        jQuery('.solr_error').html('');
                        jQuery('.img-succ').css('display', 'inline');
                        jQuery('#settings_conf_form').submit();
                    }
                    else if (data1 == 1)
                        jQuery('.solr_error').text('Error in detecting solr instance');
                    else
                        jQuery('.solr_error').html(data1);

                },
                error: function (req, status, error) {

                    jQuery('.img-load').css('display', 'none');

                    jQuery('.solr_error').text('Timeout: we had no response from your Solr server in less than 10 seconds. It\'s probably because port ' + port + ' is blocked. Please try another port, for instance 443, or contact your hosting provider to unblock port ' + port + '.');
                }
            });

        }


    })


    jQuery('.plus_icon').click(function () {
        jQuery(this).parent().addClass('facet_selected');
        jQuery(this).hide();
        jQuery(this).siblings().css('display', 'inline');
    })

    jQuery('.minus_icon').click(function () {
        jQuery(this).parent().removeClass('facet_selected');
        jQuery(this).hide();
        jQuery(this).siblings().css('display', 'inline');
    })
    jQuery("#sortable1").sortable(
        {
            connectWith: ".connectedSortable",
            stop: function (event, ui) {
                jQuery('.connectedSortable').each(function () {
                    result = "";
                    jQuery(this).find(".facet_selected").each(function () {
                        result += jQuery(this).attr('id') + ",";
                    });
                    result = result.substring(0, result.length - 1);

                    jQuery("#select_fac").val(result);
                });
            }
        });


});
