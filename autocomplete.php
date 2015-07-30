<?php
function wdm_return_solr_rows() {
	if ( isset( $_POST['security'] )
	     && wp_verify_nonce( $_POST['security'], 'nonce_for_autocomplete' )
	) {
		if ( session_id() == '' ) {
			session_start();
		}

		$input = $_POST['word'];

		$solr_options = get_option( 'wdm_solr_conf_data' );
		$host_type    = $solr_options['host_type'];

		$postix = '_goto';
		if ( $host_type == 'self_hosted' ) {
			$postix = '';
		}

		// Create session cache is necessary
		if ( !isset( $_SESSION[ $host_type ] ) ) {
			$_SESSION[ 'wdm-host' . $postix ]  = $solr_options[ 'solr_host' . $postix ];
			$_SESSION[ 'wdm-port' . $postix ]  = $solr_options[ 'solr_port' . $postix ];
			$_SESSION[ 'wdm-path' . $postix ]  = $solr_options[ 'solr_path' . $postix ];
			$_SESSION[ 'wdm-user' . $postix ]  = $solr_options[ 'solr_key' . $postix ];
			$_SESSION[ 'wdm-pwd' . $postix ]   = $solr_options[ 'solr_secret' . $postix ];
			$_SESSION[ 'wdm-proto' . $postix ] = $solr_options[ 'solr_protocol' . $postix ];

			// Cache is created
			$_SESSION[ $host_type ] = true;
		}

		$host     = $_SESSION[ 'wdm-host' . $postix ];
		$port     = $_SESSION[ 'wdm-port' . $postix ];
		$spath    = $_SESSION[ 'wdm-path' . $postix ];
		$username = $_SESSION[ 'wdm-user' . $postix ];
		$password = $_SESSION[ 'wdm-pwd' . $postix ];
		$protocol = $_SESSION[ 'wdm-proto' . $postix ];

		if ( $host_type == 'self_hosted' ) {
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
		} else {
			$config = array(
				"endpoint" =>
					array(
						"localhost1" => array(
							"scheme"   => $protocol,
							"host"     => $host,
							"port"     => $port,
							"path"     => $spath,
							'username' => $username,
							'password' => $password
						)
					)
			);
		}


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

