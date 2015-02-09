<?php

require_once plugin_dir_path( __FILE__ ) . 'classes/extensions/wpsolr-extensions.php';

class wp_Solr {

	public $client;
	public $select_query;
	protected $config;

	// Array of active extension objects
	protected $wpsolr_extensions;

	public function __construct() {

		// Load active extensions
		$this->wpsolr_extensions = new WpSolrExtensions();


		$path = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		require_once $path;

		$solr_options = get_option( 'wdm_solr_conf_data' );

		if ( $solr_options['host_type'] == 'self_hosted' ) {

			if ( $solr_options['solr_host'] != '' ) {
				$host = $solr_options['solr_host'];
			}

			if ( $solr_options['solr_path'] != '' ) {
				$path = $solr_options['solr_path'];
			}


			if ( $solr_options['solr_port'] != '' ) {
				$port = $solr_options['solr_port'];
			}

			$config = array(
				"endpoint" =>
					array(
						"localhost" => array(
							"host" => $host,
							"port" => $port,
							"path" => $path
						)
					)
			);

		} else if ( $solr_options['host_type'] == 'other_hosted' ) {

			if ( $solr_options['solr_host_goto'] != '' ) {
				$host = $solr_options['solr_host_goto'];
			}

			if ( $solr_options['solr_path_goto'] != '' ) {
				$path = $solr_options['solr_path_goto'];
			}


			if ( $solr_options['solr_port_goto'] != '' ) {
				$port = $solr_options['solr_port_goto'];
			}

			if ( $solr_options['solr_protocol_goto'] != '' ) {
				$protocol = $solr_options['solr_protocol_goto'];
			}

			$username = $solr_options['solr_key_goto'];
			$password = $solr_options['solr_secret_goto'];
			$config   = array(
				'endpoint' => array(
					'localhost1' => array(
						'scheme'   => "$protocol",
						'host'     => "$host",
						'username' => "$username",
						'password' => "$password",
						'port'     => "$port",
						'path'     => "$path"
					)
				)
			);
		}

		$this->client = new Solarium\Client( $config );

	}

	public function get_solr_status() {
		$solr_options = get_option( 'wdm_solr_conf_data' );

		$client = $this->client;

		$ping = $client->createPing();

		$result = $client->ping( $ping );
		$res    = $result->getStatus();

		return $res;

	}

	public function delete_documents() {

		$client = $this->client;

		$deleteQuery = $client->createUpdate();
		$deleteQuery->addDeleteQuery( '*:*' );
		$deleteQuery->addCommit();

		$result = $client->update( $deleteQuery );

		// Store 0 in # of index documents
		wp_Solr::update_hosting_option( 'solr_docs', 0 );

		// Reset last indexed post date
		wp_Solr::update_hosting_option( 'solr_last_post_date_indexed', '1000-01-01 00:00:00' );

		// Update nb of documents updated/added
		wp_Solr::update_hosting_option( 'solr_docs_added_or_updated_last_operation', - 1 );

		return $result->getStatus();

	}

	public function update_hosting_option( $option, $option_value ) {

		update_option( wp_Solr::get_hosting_postfixed_option( $option ), $option_value );
	}

	public function get_hosting_postfixed_option( $option ) {

		$result = $option;

		$solr_options = get_option( 'wdm_solr_conf_data' );

		switch ( $solr_options['host_type'] ) {
			case 'self_hosted':
				$postfix = '_in_self_index';
				break;

			default:
				$postfix = '_in_cloud_index';
				break;
		}

		return $result . $postfix;
	}

	/*
	 * How many documents were updated/added during last indexing operation
	 */

	public function delete_document( $post ) {

		$client = $this->client;

		$deleteQuery = $client->createUpdate();
		$deleteQuery->addDeleteQuery( 'id:' . $post->ID );
		$deleteQuery->addCommit();

		$result = $client->update( $deleteQuery );


		return $result->getStatus();

	}

	public function get_count_documents() {
		$solr_options = get_option( 'wdm_solr_conf_data' );

		$client = $this->client;

		$query = $client->createSelect();
		$query->setQuery( '*:*' );
		$query->setRows( 0 );
		$resultset = $client->select( $query );

		// Store 0 in # of index documents
		wp_Solr::update_hosting_option( 'solr_docs', $resultset->getNumFound() );

		return $resultset->getNumFound();

	}

	/*Returns array of result
    * Different blocks are written for self host and other hosted index
    * Returns array of result
    * Result[0]= Spellchecker-Did you mean
    * Result[1]= Array of Facets
    * Result[2]= No of documents found
    * Result[3]= Array of documents
    * Result[4]=Result info
    * */

	public function get_count_documents_indexed_last_operation( $default_value = - 1 ) {

		return wp_Solr::get_hosting_option( 'solr_docs_added_or_updated_last_operation', $default_value );

	}

	public function get_hosting_option( $option, $default_value ) {

		// Get option value. Replace by default value if undefined.
		$result = get_option( wp_Solr::get_hosting_postfixed_option( $option ), $default_value );

		return $result;
	}


	/*
	 * Manage options by hosting mode
	 * Use a dedicated postfix added to the option name.
	 */

	public function get_search_results( $term, $facet_options, $start, $sort ) {

		$output        = array();
		$search_result = array();

		$ind_opt = get_option( 'wdm_solr_form_data' );
		$res_opt = get_option( 'wdm_solr_res_data' );
		$fac_opt = get_option( 'wdm_solr_facet_data' );

		$number_of_res = $res_opt['no_res'];
		if ( $number_of_res == '' ) {
			$number_of_res = 20;
		}

		$field_comment = isset( $ind_opt['comments'] ) ? $ind_opt['comments'] : '';
		$options       = $fac_opt['facets'];


		$msg    = '';
		$client = $this->client;
		$term   = str_replace( ' ', '\ ', $term );

		$query = $client->createSelect();

		$query->setQuery( $term );

		// Add extensions query filters
		do_action( WpSolrExtensions::ACTION_SOLR_ADD_QUERY_FIELDS, get_current_user_id(), $query );


		switch ( $sort ) {
			case 'new':
				$sort_field = 'date';
				$sort_value = $query::SORT_DESC;
				break;
			case 'old':
				$sort_field = 'date';
				$sort_value = $query::SORT_ASC;
				break;
			case 'mcomm';
				$sort_field = 'numcomments';
				$sort_value = $query::SORT_DESC;
				break;
			case 'lcomm':
				$sort_field = 'numcomments';
				$sort_value = $query::SORT_ASC;
				break;
			default:
				$sort_field = 'id';
				$sort_value = $query::SORT_DESC;
				break;
		}

		$query->addSort( $sort_field, $sort_value );
		$query->setQueryDefaultOperator( 'AND' );


		if ( $res_opt['spellchecker'] == 'spellchecker' ) {

			$spellChk = $query->getSpellcheck();
			$spellChk->setCount( 10 );
			$spellChk->setCollate( true );
			$spellChk->setExtendedResults( true );
			$spellChk->setCollateExtendedResults( true );
			$resultset = $client->select( $query );

			$spell_msg      = '';
			$spellChkResult = $resultset->getSpellcheck();
			if ( ! $spellChkResult->getCorrectlySpelled() ) {
				$collations = $spellChkResult->getCollations();
				$term       = '';
				foreach ( $collations as $collation ) {
					foreach ( $collation->getCorrections() as $input => $correction ) {
						$term .= $correction;
					}
				}

				if ( strlen( $term ) > 0 ) {
					$err_msg = 'Did you mean: <b>' . $term . '</b><br />';

					$query->setQuery( $term );

				}
				$search_result[] = $err_msg;

			} else {
				$search_result[] = 0;
			}

		} else {
			$search_result[] = 0;
		}
		$fac_count = $res_opt['no_fac'];
		if ( $fac_count == '' ) {
			$fac_count = 20;
		}

		if ( $options != '' ) {

			$facets_array = explode( ',', $fac_opt['facets'] );

			$facetSet = $query->getFacetSet();
			$facetSet->setMinCount( 1 );
			// $facetSet->;
			foreach ( $facets_array as $facet ) {
				$fact = strtolower( $facet );

				$facetSet->createFacetField( "$fact" )->setField( "$fact" )->setLimit( $fac_count );

			}
		}
		$resultset = $client->select( $query );
		if ( $options != '' ) {
			foreach ( $facets_array as $facet ) {

				$fact      = strtolower( $facet );
				$facet_res = $resultset->getFacetSet()->getFacet( "$fact" );

				foreach ( $facet_res as $value => $count ) {
					$output[ $facet ][] = array( $value, $count );
				}


			}
			$search_result[] = $output;

		} else {
			$search_result[] = 0;
		}

		$bound = '';
		if ( $facet_options != null || $facet_options != '' ) {
			$f_array = explode( ':', $facet_options );

			$fac_field = strtolower( $f_array[0] );
			$fac_type  = isset( $f_array[1] ) ? $f_array[1] : '';


			if ( $fac_field != '' && $fac_type != '' ) {
				$fac_fd = "$fac_field";
				$fac_tp = str_replace( ' ', '\ ', $fac_type );

				$query->addFilterQuery( array( 'key' => "$fac_fd", 'query' => "$fac_fd:$fac_tp" ) );
			}

			if ( isset( $f_array[2] ) && $f_array[2] != '' ) {
				$bound = $f_array[2];
			}

		}


		if ( $start == 0 || $start == 1 ) {
			$st = 0;

		} else {
			$st = ( ( $start - 1 ) * $number_of_res );

		}

		if ( $bound != '' && $bound < $number_of_res ) {

			$query->setStart( $st )->setRows( $bound );

		} else {
			$query->setStart( $st )->setRows( $number_of_res );

		}


		$resultset = $client->select( $query );

		$found = $resultset->getNumFound();

		if ( $bound != '' ) {
			$search_result[] = $bound;


		} else {
			$search_result[] = $found;

		}

		$hl = $query->getHighlighting();
		$hl->getField( 'title' )->setSimplePrefix( '<b>' )->setSimplePostfix( '</b>' );
		$hl->getField( 'content' )->setSimplePrefix( '<b>' )->setSimplePostfix( '</b>' );


		if ( $field_comment == 1 ) {
			$hl->getField( 'comments' )->setSimplePrefix( '<b>' )->setSimplePostfix( '</b>' );
		}

		$resultSet = '';
		$resultSet = $client->select( $query );


		$results      = array();
		$highlighting = $resultSet->getHighlighting();


		$i       = 1;
		$cat_arr = array();
		foreach ( $resultset as $document ) {
			$id        = $document->id;
			$pid       = $document->PID;
			$name      = $document->title;
			$content   = $document->content;
			$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $id ) );

			$no_comments = $document->numcomments;
			if ( $field_comment == 1 ) {
				$comments = $document->comments;
			}
			$date = date( 'm/d/Y', strtotime( $document->displaydate ) );

			if ( property_exists( $document, "categories" ) ) {
				$cat_arr = $document->categories;
			}


			$cat  = implode( ',', $cat_arr );
			$auth = $document->author;

			$cont = substr( $content, 0, 200 );

			$url = get_permalink( $id );

			$highlightedDoc = $highlighting->getResult( $document->id );
			$cont_no        = 0;
			$comm_no        = 0;
			if ( $highlightedDoc ) {

				foreach ( $highlightedDoc as $field => $highlight ) {
					$msg = '';
					if ( $field == 'title' ) {
						$name = implode( ' (...) ', $highlight );

					} else if ( $field == 'content' ) {
						$cont    = implode( ' (...) ', $highlight );
						$cont_no = 1;
					} else if ( $field == 'comments' ) {
						$comments = implode( ' (...) ', $highlight );
						$comm_no  = 1;
					}

				}


			}
			$msg = '';
			$msg .= "<div id='res$i'><div class='p_title'><a href='$url'>$name</a></div>";

			$image_fragment = '';
			// Display first image
			if ( is_array( $image_url ) && count( $image_url ) > 0 ) {
				$image_fragment .= "<img class='wdm_result_list_thumb' src='$image_url[0]' />";
			}

			// Format content text a little bit
			$cont = str_replace( '&nbsp;', '', $cont );
			$cont = str_replace( '  ', ' ', $cont );
			$cont = ucfirst( trim( $cont ) );
			$cont .= '...';

			//if ( $cont_no == 1 ) {
			if ( false ) {
				$msg .= "<div class='p_content'>$image_fragment $cont - <a href='$url'>Content match</a></div>";
			} else {
				$msg .= "<div class='p_content'>$image_fragment $cont</div>";
			}
			if ( $comm_no == 1 ) {
				$msg .= "<div class='p_comment'>" . $comments . "-<a href='$url'>Comment match</a></div>";
			}

			// Groups bloc - Bottom right
			$wpsolr_groups_message = apply_filters( WpSolrExtensions::FILTER_SOLR_DOCUMENT_ADD_GROUPS, get_current_user_id(), $document );
			if ( isset( $wpsolr_groups_message ) ) {

				// Display groups of this user which owns at least one the document capability
				$message = $wpsolr_groups_message['message'];
				$msg .= "<div class='p_misc'>$message";
				$msg .= "</div>";
				$msg .= '<br/>';

			}

			// Informative bloc - Bottom right
			$msg .= "<div class='p_misc'>By <span class='pauthor'>$auth</span>";
			$msg .= empty( $cat ) ? "" : ", in <span class='pcat'>$cat</span>";
			$msg .= ", on <span class='pdate'>$date</span>";
			$msg .= empty( $no_comments ) ? "" : ", <span class='pcat'> $no_comments comments</span>";
			$msg .= "</div>";

			// End of snippet bloc
			$msg .= "</div><hr>";

			array_push( $results, $msg );
			$i = $i + 1;
		}
		//  $msg.='</div>';


		if ( count( $results ) < 0 ) {
			$search_result[] = 0;
		} else {
			$search_result[] = $results;
		}

		$fir = $st + 1;

		$last = $st + $number_of_res;
		if ( $last > $found ) {
			$last = $found;
		} else {
			$last = $st + $number_of_res;
		}

		$search_result[] = "<span class='infor'>Showing $fir to $last results out of $found</span>";


		return $search_result;
	}

	/*
	 * Manage options by hosting mode
	 * Use a dedicated postfix added to the option name.
	 */

	public function auto_complete_suggestions( $input ) {
		$res = array();

		$client = $this->client;


		$suggestqry = $client->createSuggester();
		$suggestqry->setHandler( 'suggest' );
		$suggestqry->setDictionary( 'suggest' );

		$suggestqry->setQuery( $input );
		$suggestqry->setCount( 5 );
		$suggestqry->setCollate( true );
		$suggestqry->setOnlyMorePopular( true );

		$resultset = $client->suggester( $suggestqry );

		foreach ( $resultset as $term => $termResult ) {
			// $msg.='<strong>' . $term . '</strong><br/>';

			foreach ( $termResult as $result ) {

				array_push( $res, $wd );
			}
		}

		$result = json_encode( $res );

		return $result;
	}

	/*
	 * Manage options by hosting mode
	 * Use a dedicated postfix added to the option name.
	 */

	public function index_data( $post = null ) {

		global $wpdb;

		// Last post date set in previous call. We begin with posts published after.
		$lastPostDate = wp_Solr::get_hosting_option( 'solr_last_post_date_indexed', '1000-01-01 00:00:00' );

		// Reset last operation result
		wp_Solr::update_count_documents_indexed_last_operation( 0 );

		$batch_size = 100;
		$res_final  = 0;
		$cnt        = 0;

		$tbl   = $wpdb->prefix . 'posts';
		$where = '';

		$client      = $this->client;
		$updateQuery = $client->createUpdate();
		// Get body of attachment
		$extractQuery = $client->createExtract();

		$ind_opt = get_option( 'wdm_solr_form_data' );

		$post_types = $ind_opt['p_types'];
		$exclude_id = $ind_opt['exclude_ids'];
		$ex_ids     = array();
		$ex_ids     = explode( ',', $exclude_id );
		$posts      = explode( ',', $post_types );

		// Build the WHERE clause
		$where_p = '';
		foreach ( $posts as $post_type ) {
			if ( $post_type != 'attachment' ) {
				// Where clause for *p*osts
				$where_p .= " post_type='$post_type' OR";
			} else {
				// Where clause for *a*ttachments
				// We check the publish status of the parent post later on, if post_status='inherit'
				$where_a = "( post_status='publish' OR post_status='inherit' ) AND post_type='attachment'";
			}
		}
		if ( isset( $where_p ) ) {
			// Remove the last " OR"
			$where_p = substr( $where_p, 0, - 3 );
			$where   = "post_status='publish' AND ( $where_p )";
			if ( isset( $where_a ) ) {
				$where = "( $where ) OR ( $where_a )";
			}
		} elseif ( isset( $where_a ) ) {
			$where = $where_a;
		}


		// Build the query
		$query = "";
		// We need post_parent and post_type, too, to handle attachments
		$query .= " SELECT ID, post_modified, post_parent, post_type ";
		$query .= " FROM $tbl ";
		$query .= " WHERE ";
		$query .= " post_modified > %s ";
		if ( isset( $post ) ) {
			// Add condition on the $post
			$query .= " AND ID = %d";
		}
		$query .= " AND ( $where ) ";
		$query .= " ORDER BY ID ASC ";
		$query .= " LIMIT $batch_size ";

		$documents = array();
		$doc_count = 0;
		while ( true ) {

			// Execute query (retrieve posts IDs, parents and types)
			if ( isset( $post ) ) {
				$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $lastPostDate, $post->ID ), ARRAY_A );
			} else {
				$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $lastPostDate ), ARRAY_A );
			}

			// Aggregate current batch IDs in one Solr update statement
			$postcount = count( $ids_array );

			if ( $postcount == 0 ) {
				// No more documents to index, stop now by exiting the loop
				break;
			}


			$lastPostDate = end( $ids_array )['post_modified'];

			// Get the ID of every published post
			// We need these to be able to check whether a parent post of an attachment has been published
			$published_ids = array();
			foreach ( $ids_array as $id_array ) {
				$published_ids[] = $id_array['ID'];
			}

			for ( $idx = 0; $idx < $postcount; $idx ++ ) {
				$postid = $ids_array[ $idx ]['ID'];

				// If post is not on blacklist
				if ( ! in_array( $postid, $ex_ids ) ) {
					// If post is not an attachment
					if ( $ids_array[ $idx ]['post_type'] != 'attachment' ) {
						// Count this post
						$doc_count ++;
						// Get the posts data
						$documents[] = wp_Solr::create_solr_document_from_post_or_attachment( $updateQuery, $ind_opt, get_post( $postid ) );
					} else {
						// Post is of type "attachment"
						// Post's parent has been published
						if ( in_array( $ids_array[ $idx ]['post_parent'], $published_ids ) ) {
							// Count this post
							$doc_count ++;
							// Get the attachments body
							$attachment_body = wp_Solr::get_attachment_body( $extractQuery, get_post( $postid ) );
							// Get the posts data
							$documents[] = wp_Solr::create_solr_document_from_post_or_attachment( $updateQuery, $ind_opt, get_post( $postid ), $attachment_body );
						}
					}
				}
			}

			if ( empty( $documents ) || ! isset( $documents ) ) {
				// No more documents to index, stop now by exiting the loop
				break;
			}

			// Send batch documents to Solr
			$res_final = wp_Solr::send_posts_or_attachments_to_solr_index( $updateQuery, $documents );

			// Solr error, or only $post to index: exit loop
			if ( ( ! $res_final ) OR isset( $post ) ) {
				break;
			}

			// Don't send twice the same documents
			$documents = array();

			// Store last post date sent to Solr
			wp_Solr::update_hosting_option( 'solr_last_post_date_indexed', $lastPostDate );

			// Update nb of documents updated/added
			wp_Solr::update_count_documents_indexed_last_operation( $doc_count );

		}

		return $res_final;

	}


	/*
	 * Fetch posts and attachments,
	 * Transorm them in Solr documents,
	 * Send them in packs to Solr
	 */

	public function update_count_documents_indexed_last_operation( $count = null ) {

		return wp_Solr::update_hosting_option( 'solr_docs_added_or_updated_last_operation', is_null( $count ) ? - 1 : $count );

	}

	public function create_solr_document_from_post_or_attachment( $updateQuery, $opt, $post, $attachment_body = false ) {

		$pid    = $post->ID;
		$ptitle = $post->post_title;
		if ( $attachment_body ) {
			// Post is an attachment: we get the document body from the function call
			$pcontent = $attachment_body;
		} else {
			// Post is NOT an attachment: we get the document body from the post object
			$pcontent = $post->post_content;
		}
		$pauth_info       = get_userdata( $post->post_author );
		$pauthor          = $pauth_info->display_name;
		$pauthor_s        = get_author_posts_url( $pauth_info->ID, $pauth_info->user_nicename );
		$ptype            = $post->post_type;
		$pdate            = solr_format_date( $post->post_date_gmt );
		$pmodified        = solr_format_date( $post->post_modified_gmt );
		$pdisplaydate     = $post->post_date;
		$pdisplaymodified = $post->post_modified;
		$purl             = get_permalink( $pid );
		$pcomments        = array();
		$comments_con     = array();
		$comm             = isset( $opt['comments'] ) ? $opt['comments'] : '';

		$numcomments = 0;
		if ( $comm ) {
			$comments_con = array();

			$comments = get_comments( "status=approve&post_id={$post->ID}" );
			foreach ( $comments as $comment ) {
				array_push( $comments_con, $comment->comment_content );
				$numcomments += 1;
			}

		}
		$pcomments    = $comments_con;
		$pnumcomments = $numcomments;

		$cats       = array();
		$categories = get_the_category( $post->ID );
		if ( ! $categories == null ) {
			foreach ( $categories as $category ) {
				array_push( $cats, $category->cat_name );


			}
		}

		$tag_array = array();
		$tags      = get_the_tags( $post->ID );
		if ( ! $tags == null ) {
			foreach ( $tags as $tag ) {
				array_push( $tag_array, $tag->name );

			}
		}


		$solr_options = get_option( 'wdm_solr_conf_data' );

		$doc1        = $updateQuery->createDocument();
		$numcomments = 0;

		$doc1->id      = $pid;
		$doc1->PID     = $pid;
		$doc1->title   = $ptitle;
		$doc1->content = strip_tags( $pcontent );

		$doc1->author          = $pauthor;
		$doc1->author_s        = $pauthor_s;
		$doc1->type            = $ptype;
		$doc1->date            = $pdate;
		$doc1->modified        = $pmodified;
		$doc1->displaydate     = $pdisplaydate;
		$doc1->displaymodified = $pdisplaymodified;

		$doc1->permalink   = $purl;
		$doc1->comments    = $pcomments;
		$doc1->numcomments = $pnumcomments;
		$doc1->categories  = $cats;

		$doc1->tags = $tag_array;

		$custom_taxo = array();
		$taxo        = $opt['taxonomies'];
		$aTaxo       = explode( ',', $taxo );
		$newTax      = array();
		foreach ( $aTaxo as $a ) {
			if ( substr( $a, ( strlen( $a ) - 4 ), strlen( $a ) ) == "_str" ) {
				$a = substr( $a, 0, ( strlen( $a ) - 4 ) );
			}
			array_push( $newTax, $a );

		}

		$taxonomies = (array) get_taxonomies( array( '_builtin' => false ), 'names' );
		foreach ( $taxonomies as $parent ) {
			if ( in_array( $parent, $newTax ) ) {
				$terms = get_the_terms( $post->ID, $parent );
				if ( (array) $terms === $terms ) {
					$parent = strtolower( str_replace( ' ', '_', $parent ) );
					foreach ( $terms as $term ) {
						$nm1        = $parent . '_str';
						$nm2        = $parent . '_srch';
						$doc1->$nm1 = $term->name;
						$doc1->$nm2 = $term->name;
					}
				}
			}
		}


		$custom  = $opt['cust_fields'];
		$aCustom = explode( ',', $custom );
		if ( count( $aCustom ) > 0 ) {
			if ( count( $custom_fields = get_post_custom( $post->ID ) ) ) {
				foreach ( (array) $aCustom as $field_name ) {
					if ( substr( $field_name, ( strlen( $field_name ) - 4 ), strlen( $field_name ) ) == "_str" ) {
						$field_name = substr( $field_name, 0, ( strlen( $field_name ) - 4 ) );
					}
					if ( isset( $custom_fields[ $field_name ] ) ) {
						$field = (array) $custom_fields[ $field_name ];

						$field_name = strtolower( str_replace( ' ', '_', $field_name ) );

						// Add custom field array of values
						$nm1        = $field_name . '_str';
						$nm2        = $field_name . '_srch';
						$doc1->$nm1 = $field;
						$doc1->$nm2 = $field;

					}
				}
			}
		}

		return $doc1;

	}

	public function get_attachment_body( $extractQuery, $post ) {
		$solr_options = get_option( 'wdm_solr_conf_data' );

		try {
			// Set URL to attachment
			$extractQuery->setFile( get_attached_file( $post->ID ) );
			$doc1 = $extractQuery->createDocument();
			$extractQuery->setDocument( $doc1 );
			// We don't want to add the document to the solr index now
			$extractQuery->addParam( 'extractOnly', 'true' );
			// Try to extract the document body
			$client   = $this->client;
			$result   = $client->extract( $extractQuery );
			$response = $result->getResponse()->getBody();
			$body     = preg_replace( '/^.*?\<body\>(.*?)\<\/body\>.*$/i', '\1', $response );
			$body     = str_replace( '\n', ' ', $body );
		} catch ( Exception $e ) {
			throw new Exception( 'Error on attached file "' . $post->post_title . '": <br/>' . $e->getMessage() );
		}

		return $body;
	}

	public function send_posts_or_attachments_to_solr_index( $updateQuery, $documents ) {

		$client = $this->client;
		$updateQuery->addDocuments( $documents );
		$updateQuery->addCommit();
		$result = $client->update( $updateQuery );

		return $result;

	}


}
