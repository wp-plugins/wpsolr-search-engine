<?php
function wdm_return_solr_rows() {
	if ( isset( $_POST['security'] )
	     && wp_verify_nonce( $_POST['security'], 'nonce_for_autocomplete' )
	) {
		if ( session_id() == '' ) {
			session_start();
		}

		$input = $_POST['word'];

		if ( $_SESSION['wdm-host'] == '' ) {
			$solr_options         = get_option( 'wdm_solr_conf_data' );
			$_SESSION['wdm-host'] = $solr_options['solr_host'];
			$_SESSION['wdm-port'] = $solr_options['solr_port'];
			$_SESSION['wdm-path'] = $solr_options['solr_path'];
		}
		$host  = $_SESSION['wdm-host'];
		$port  = $_SESSION['wdm-port'];
		$spath = $_SESSION['wdm-path'];

		$config = array(
			"endpoint" =>
				array(
					"localhost" => array(
						"host" => $host,
						"port" => $port,
						"path" => $spath
					)
				)
		);
		require( 'vendor/autoload.php' );
		$client = new Solarium\Client( $config );
		$input  = strtolower( $input );
		$res    = array();

		$suggestqry = $client->createSuggester();
		$suggestqry->setHandler( 'suggest' );
		$suggestqry->setDictionary( 'suggest' );

		$suggestqry->setQuery( $input );
		$suggestqry->setCount( 5 );
		$suggestqry->setCollate( true );
		$suggestqry->setOnlyMorePopular( true );

		$resultset = $client->suggester( $suggestqry );

		foreach ( $resultset as $term => $termResult ) {
			foreach ( $termResult as $result ) {

				array_push( $res, $result );
			}
		}

		$result1 = json_encode( $res );

		echo $result1;
	}
	die();
}

add_action( 'wp_ajax_wdm_return_solr_rows', 'wdm_return_solr_rows' );
add_action( 'wp_ajax_nopriv_wdm_return_solr_rows', 'wdm_return_solr_rows' );
