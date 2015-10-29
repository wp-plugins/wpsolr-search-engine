jQuery(document).ready(function () {

    var search_term = '';
    var data_gl = '';
    var wdm_object_for_search_values = new Object();


    jQuery(document).on('focus', '.search-field', function () {
        var admin_path = jQuery('#path_to_admin').val() + 'admin-ajax.php';
        var wdm_action = jQuery('#path_to_fold').val();

        jQuery(this).typeahead({
            ajax: {
                url: admin_path,
                triggerLength: 1,
                method: "post",
                loadingClass: "loading-circle",
                preDispatch: function (query) {

                    jQuery('.search-field').addClass('loading_sugg');

                    return {
                        action: wdm_action,
                        word: query,
                        security: jQuery('#ajax_nonce').val()
                    }
                },
                preProcess: function (data) {
                    //alert(data);
                    jQuery('.search-field').removeClass('loading_sugg');
                    return data;
                }
            }
        });
    })


    jQuery(document).on('click', '.select_opt', function () {
        opts = jQuery(this).attr('id');
        jQuery('#sel_fac_field').val(opts);
        if (jQuery('.select_field').length > 0)
            sort_opt = jQuery('.select_field').val();
        else
            sort_opt = 'new';
        que = jQuery('#search_que').val();

        path = jQuery('#path_to_admin').val();

        jQuery('.loading_res').css('display', 'block');
        jQuery('.results-by-facets').css('display', 'none');
        jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: {action: 'return_solr_results', 'opts': opts, 'query': que, 'page_no': 0, 'sort_opt': sort_opt},
            success: function (data1) {


                data = JSON.parse(data1);

                jQuery('#search_que').val(que);
                jQuery('.loading_res').css('display', 'none');
                jQuery('.results-by-facets').html(data[0]);
                jQuery('.paginate_div').html(data[1]);
                jQuery('.res_info').html(data[2]);
                jQuery('.results-by-facets').css('display', 'block');

            },
            error: function () {

            }
        });
    });


    jQuery(document).on('change', '.select_field', function () {

        sort_opt = jQuery(this).val();
        if (jQuery('#sel_fac_field').length > 0)
            opts = jQuery('#sel_fac_field').val();
        else
            opts = '';
        que = jQuery('#search_que').val();

        path = jQuery('#path_to_admin').val();

        jQuery('.loading_res').css('display', 'block');
        jQuery('.results-by-facets').css('display', 'none');
        jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: {
                action: 'return_solr_results', 'opts': opts, 'query': que, 'page_no': 0, 'sort_opt': sort_opt
            },
            success: function (data1) {
                data = JSON.parse(data1);
                jQuery('#search_que').val(que);
                jQuery('.loading_res').css('display', 'none');
                jQuery('.results-by-facets').html(data[0]);
                jQuery('.paginate_div').html(data[1]);
                jQuery('.res_info').html(data[2]);
                jQuery('.results-by-facets').css('display', 'block');

            },
            error: function () {

            }
        });
    });


    jQuery(document).on('click', '.paginate', function () {
        num = jQuery(this).attr('id');
        sort_opt = jQuery('.select_field').val();
        que = jQuery('#search_que').val();
        opts = jQuery('#sel_fac_field').val();
        path = jQuery('#path_to_admin').val();
        jQuery('.loading_res').css('display', 'block');
        jQuery('.results-by-facets').css('display', 'none');
        jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: {action: 'return_solr_results', 'opts': opts, 'query': que, 'page_no': num, 'sort_opt': sort_opt},
            success: function (data1) {

                data = JSON.parse(data1);
                jQuery('#search_opt').val(que);
                jQuery('.loading_res').css('display', 'none');
                jQuery('.results-by-facets').html(data[0]);
                jQuery('.paginate_div').html(data[1]);
                jQuery('.res_info').html(data[2]);
                jQuery('.results-by-facets').css('display', 'block');

            },
            error: function () {

            }
        });
    });


});

