<?php

include( dirname( __FILE__ ) . '/class-wp-solr.php' );

// Load localization class
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LOCALIZATION, true );

function solr_format_date( $thedate ) {
	$datere  = '/(\d{4}-\d{2}-\d{2})\s(\d{2}:\d{2}:\d{2})/';
	$replstr = '${1}T${2}Z';

	return preg_replace( $datere, $replstr, $thedate );
}

add_action( 'wp_head', 'add_scripts' );
function add_scripts() {
	wp_enqueue_style( 'solr_auto_css', plugins_url( 'css/bootstrap.min.css', __FILE__ ) );
	wp_enqueue_style( 'solr_frontend', plugins_url( 'css/style.css', __FILE__ ) );
	wp_enqueue_script( 'solr_auto_js1', plugins_url( 'js/bootstrap-typeahead.js', __FILE__ ), array( 'jquery' ), false, true );
	wp_enqueue_script( 'solr_autocomplete', plugins_url( 'js/autocomplete_solr.js', __FILE__ ), array( 'solr_auto_js1' ), false, true );
}

function fun_search_indexed_data() {

	$search_que = '';
	if ( isset( $_GET['search'] ) ) {
		$search_que = $_GET['search'];
	}

	$ad_url        = admin_url();
	$get_page_info = get_page_by_title( 'Search Results' );
	$url           = get_permalink( $get_page_info->ID );
	$solr_options  = get_option( 'wdm_solr_conf_data' );


	$k     = '';
	$sec   = '';
	$proto = '';


	if ( $solr_options['host_type'] == 'self_hosted' ) {
		$_SESSION['wdm-host'] = $solr_options['solr_host'];
		$_SESSION['wdm-port'] = $solr_options['solr_port'];
		$_SESSION['wdm-path'] = $solr_options['solr_path'];


	} else {

		//$wdm_typehead_request_handler = 'wdm_return_goto_solr_rows';
		$_SESSION['wdm-ghost']  = $solr_options['solr_host_goto'];
		$_SESSION['wdm-gport']  = $solr_options['solr_port_goto'];
		$_SESSION['wdm-gpath']  = $solr_options['solr_path_goto'];
		$_SESSION['wdm-guser']  = $solr_options['solr_key_goto'];
		$_SESSION['wdm-gpwd']   = $solr_options['solr_secret_goto'];
		$_SESSION['wdm-gproto'] = $solr_options['solr_protocol_goto'];

	}

	// Load localization options
	$localization_options = OptionLocalization::get_options();

	$wdm_typehead_request_handler = 'wdm_return_solr_rows';

	echo "<div class='cls_search' style='width:100%'> <form action='$url' method='get'  class='search-frm' >";
	echo '<input type="hidden" value="' . $wdm_typehead_request_handler . '" id="path_to_fold">';
	echo '<input type="hidden" value="' . $ad_url . '" id="path_to_admin">';
	echo '<input type="hidden" value="' . $search_que . '" id="search_opt">';

	$ajax_nonce = wp_create_nonce( "nonce_for_autocomplete" );


	$solr_form_options = get_option( 'wdm_solr_res_data' );
	$opt               = $solr_form_options['default_search'];

	$fac_opt = get_option( 'wdm_solr_facet_data' );

	$get_page_info = get_page_by_title( 'Search Results' );

	// Block or start search after autocomplete selecton
	$is_after_autocomplete_block_submit = isset( $solr_form_options['is_after_autocomplete_block_submit'] ) ? $solr_form_options['is_after_autocomplete_block_submit'] : '0';

	echo $form = '
        <div class="ui-widget">
	<input type="hidden" name="page_id" value="' . $get_page_info->ID . '" />
	<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">
        <input type="text" placeholder="' . OptionLocalization::get_term( $localization_options, 'search_form_edit_placeholder' ) . '" value="' . $search_que . '" name="search" id="search_que" class="search-field sfl2" autocomplete="off"/>
	<input type="submit" value="' . OptionLocalization::get_term( $localization_options, 'search_form_button_label' ) . '" id="searchsubmit" style="position:relative;width:auto">
	<input type="hidden" value="' . $is_after_autocomplete_block_submit . '" id="is_after_autocomplete_block_submit">
<div style="clear:both"></div>
        </div>
        </form>';

	echo '</div>';
	echo "<div class='cls_results'>";
	if ( $search_que != '' && $search_que != '*:*' ) {

		$solr = new wp_Solr();

		$res     = 0;
		$options = $fac_opt['facets'];
		if ( $res == 0 ) {

			// Use default sort
			$sort_opt     = get_option( 'wdm_solr_sortby_data' );
			$sort_default = $sort_opt['sort_default'];

			try {

				$final_result = $solr->get_search_results( $search_que, '', '', $sort_default );
			} catch ( Exception $e ) {

				$message = $e->getMessage();
				echo "<span class='infor'>$message</span>";
				die();
			}

			if ( $final_result[2] == 0 ) {
				echo "<span class='infor'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_header_no_results_found' ), $search_que ) . "</span>";
			} else {
				echo '<div class="wdm_resultContainer">
                    <div class="wdm_list">';

				// Display the sort list
				$all_sort_options = get_option( 'wdm_solr_sortby_data' );
				if ( isset( $all_sort_options ) && ( $all_sort_options != '' ) ) {
					$selected_sort_values = $all_sort_options['sort'];
					if ( isset( $selected_sort_values ) && ( $selected_sort_values != '' ) ) {

						$term        = OptionLocalization::get_term( $localization_options, 'sort_header' );
						$sort_select = "<label class='wdm_label'>$term</label><select class='select_field'>";

						// Add options
						$sort_options = wp_Solr::get_sort_options();
						foreach ( explode( ',', $selected_sort_values ) as $sort_code ) {

							$sort_label = OptionLocalization::get_term( $localization_options, $sort_code );

							$selected = ( $sort_default == $sort_code ) ? 'selected' : '';
							$sort_select .= "<option value='$sort_code' $selected>$sort_label</option>";
						}

						$sort_select .= "</select>";

						echo '<div>' . $sort_select . '</div>';
					}
				}

				$res_array = $final_result[3];
				if ( $final_result[1] != '0' ) {


					if ( $options != '' && $res_array != 0 ) {

						$facets_array = explode( ',', $fac_opt['facets'] );


						$groups = sprintf( "<div><label class='wdm_label'>%s</label>
                                    <input type='hidden' name='sel_fac_field' id='sel_fac_field' value='all' >
                                    <ul class='wdm_ul'><li class='select_opt' id='all'>%s</li>",
							OptionLocalization::get_term( $localization_options, 'facets_header' ),
							OptionLocalization::get_term( $localization_options, 'facets_element_all_results' )
						);

						$facet_element = OptionLocalization::get_term( $localization_options, 'facets_element' );
						$facet_title   = OptionLocalization::get_term( $localization_options, 'facets_title' );
						foreach ( $facets_array as $arr ) {
							$field = ucfirst( $arr );
							if ( isset( $final_result[1][ $arr ] ) && count( $final_result[1][ $arr ] ) > 0 ) {
								$arr_val = $field;
								if ( substr( $arr_val, ( strlen( $arr_val ) - 4 ), strlen( $arr_val ) ) == "_str" ) {
									$arr_val = substr( $arr_val, 0, ( strlen( $arr_val ) - 4 ) );
								}
								$arr_val = str_replace( '_', ' ', $arr_val );

								$groups .= "<lh >" . sprintf( $facet_title, $arr_val ) . "</lh><br>";

								foreach ( $final_result[1][ $arr ] as $val ) {
									$name  = $val[0];
									$count = $val[1];

									$groups .= "<li class='select_opt' id='$field:$name:$count'>"
									           . sprintf( $facet_element, $name, $count )
									           . "</li>";
								}
							}

						}

						$groups .= '</ul></div>';


					}

					echo $groups;

				}

				echo '</div>
                    <div class="wdm_results">';
				if ( $final_result[0] != '0' ) {
					echo $final_result[0];
				}

				if ( $solr_form_options['res_info'] == 'res_info' && $res_array != 0 ) {
					echo '<div class="res_info">' . $final_result[4] . '</div>';
				}

				if ( $res_array != 0 ) {
					$img = plugins_url( 'images/gif-load.gif', __FILE__ );
					echo '<div class="loading_res"><img src="' . $img . '"></div>';
					echo "<div class='results-by-facets'>";
					foreach ( $res_array as $resarr ) {
						echo $resarr;
					}
					echo "</div>";
					echo "<div class='paginate_div'>";
					$total         = $final_result[2];
					$number_of_res = $solr_form_options['no_res'];
					if ( $total > $number_of_res ) {
						$pages = ceil( $total / $number_of_res );
						echo '<ul id="pagination-flickr" class="wdm_ul">';
						for ( $k = 1; $k <= $pages; $k ++ ) {
							echo "<li ><a class='paginate' href='#' id='$k'>$k</a></li>";
						}
					}
					echo '</ul></div>';

				}


				echo '</div>';
				echo '</div><div style="clear:both;"></div>';
			}
		} else {
			echo 'Unable to detect Solr instance';
		}

	}

	echo '</div>';
}


function return_solr_instance() {

	$path = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	require_once $path;


	$spath    = $_POST['spath'];
	$port     = $_POST['sport'];
	$host     = $_POST['shost'];
	$username = $_POST['skey'];
	$password = $_POST['spwd'];
	$protocol = $_POST['sproto'];

	if ( $username == '' ) {
		$config = array(
			"endpoint" =>
				array(
					"localhost" => array(
						'scheme' => $protocol,
						"host"   => $host,
						"port"   => $port,
						"path"   => $spath
					)
				)
		);

	} else {
		$config = array(
			'endpoint' => array(
				'localhost1' => array(
					'scheme'   => $protocol,
					'host'     => $host,
					'port'     => $port,
					'path'     => $spath,
					'username' => $username,
					'password' => $password
				)
			)
		);
	}


	$client = new Solarium\Client( $config );

	$ping = $client->createPing();

	try {
		$result = $client->ping( $ping );

	} catch ( Exception $e ) {

		$str_err     = "";
		$solrCode    = $e->getCode();
		$solrMessage = $e->getMessage();

		switch ( $e->getCode() ) {

			case 401:
				$str_err .= "<br /><span>The server authentification failed. Please check your user/password (Solr code http $solrCode)</span><br />";
				break;

			case 400:
			case 404:

				$str_err .= "<br /><span>We could not join your Solr server. Your Solr path could be malformed, or your Solr server down (Solr code $solrCode)</span><br />";
				break;

			default:

				$str_err .= "<span>$solrMessage ($solrCode)</span><br /><br />\n";
				break;

		}


		echo $str_err;
		echo '<br>';
		echo htmlentities( $solrMessage );

		return;

	}


}

add_action( 'wp_ajax_nopriv_' . 'return_solr_instance', 'return_solr_instance' );
add_action( 'wp_ajax_' . 'return_solr_instance', 'return_solr_instance' );


function return_solr_status() {

	$solr = new wp_Solr();
	echo $words = $solr->get_solr_status();

}

add_action( 'wp_ajax_nopriv_' . 'return_solr_status', 'return_solr_status' );
add_action( 'wp_ajax_' . 'return_solr_status', 'return_solr_status' );


function return_solr_results() {

	$query = $_POST['query'];
	$opt   = $_POST['opts'];
	$num   = $_POST['page_no'];
	$sort  = $_POST['sort_opt'];


	$solr          = new wp_Solr();
	$final_result  = $solr->get_search_results( $query, $opt, $num, $sort );
	$solr_options  = get_option( 'wdm_solr_conf_data' );
	$output        = array();
	$search_result = array();

	$res_opt = get_option( 'wdm_solr_res_data' );

	$res1  = array();
	$f_res = '';
	foreach ( $final_result[3] as $fr ) {
		$f_res .= $fr;
	}
	$res1[] = $final_result[3];


	$total         = $final_result[2];
	$number_of_res = $res_opt['no_res'];
	$paginat_var   = '';
	if ( $total > $number_of_res ) {
		$pages = ceil( $total / $number_of_res );
		$paginat_var .= '<ul id="pagination-flickr"class="wdm_ul">';
		for ( $k = 1; $k <= $pages; $k ++ ) {
			$paginat_var .= "<li ><a class='paginate' href='#' id='$k'>$k</a></li>";
		}
		$paginat_var .= '</ul>';
	}


	$res1[] = $paginat_var;
	$res1[] = $final_result[4];
	echo json_encode( $res1 );


	die();
}

add_action( 'wp_ajax_nopriv_' . 'return_solr_results', 'return_solr_results' );
add_action( 'wp_ajax_' . 'return_solr_results', 'return_solr_results' );

/*
 * Ajax call to index Solr documents
 */
function return_solr_index_data() {

	try {
		// Batch size
		$batch_size = intval( $_POST['batch_size'] );

		// nb of document sent until now
		$nb_results = intval( $_POST['nb_results'] );

		// Debug infos displayed on screen ?
		$is_debug_indexing = ( $_POST['is_debug_indexing'] === "true" );

		$solr      = new wp_Solr();
		$res_final = $solr->index_data( $batch_size, null, $is_debug_indexing );

		// Increment nb of document sent until now
		$res_final['nb_results'] += $nb_results;

		echo json_encode( $res_final );

	} catch ( Exception $e ) {

		echo json_encode(
			array(
				'nb_results'        => 0,
				'status'            => $e->getCode(),
				'message'           => htmlentities( $e->getMessage() ),
				'indexing_complete' => false
			)
		);

	}

	die();
}

add_action( 'wp_ajax_nopriv_' . 'return_solr_index_data', 'return_solr_index_data' );
add_action( 'wp_ajax_' . 'return_solr_index_data', 'return_solr_index_data' );


/*
 * Ajax call to clear Solr documents
 */
function return_solr_delete_index() {

	try {

		$solr = new wp_Solr();
		$solr->delete_documents();

	} catch ( Exception $e ) {

		echo json_encode(
			array(
				'nb_results'        => 0,
				'status'            => $e->getCode(),
				'message'           => htmlentities( $e->getMessage() ),
				'indexing_complete' => false
			)
		);

	}

	die();
}

add_action( 'wp_ajax_nopriv_' . 'return_solr_delete_index', 'return_solr_delete_index' );
add_action( 'wp_ajax_' . 'return_solr_delete_index', 'return_solr_delete_index' );