<?php
function func_reg_solr_form_setting() {
	register_setting( 'solr_conf_options', 'wdm_solr_conf_data' );
	register_setting( 'solr_form_options', 'wdm_solr_form_data' );
	register_setting( 'solr_res_options', 'wdm_solr_res_data' );
	register_setting( 'solr_facet_options', 'wdm_solr_facet_data' );
	register_setting( 'solr_sort_options', 'wdm_solr_sortby_data' );
	register_setting( 'solr_extension_groups_options', 'wdm_solr_extension_groups_data' );
	register_setting( 'solr_extension_s2member_options', 'wdm_solr_extension_s2member_data' );
	register_setting( 'solr_operations_options', 'wdm_solr_operations_data' );
}

function fun_add_solr_settings() {
	$img_url = plugins_url( 'images/WPSOLRDashicon.png', __FILE__ );
	add_menu_page( 'WPSOLR', 'WPSOLR', 'manage_options', 'solr_settings', 'fun_set_solr_options', $img_url );
	wp_enqueue_style( 'dashboard_style', plugins_url( 'css/dashboard_css.css', __FILE__ ) );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'dashboard_js1', plugins_url( 'js/dashboard.js', __FILE__ ), array(
		'jquery',
		'jquery-ui-sortable'
	) );

	$plugin_vals = array( 'plugin_url' => plugins_url( 'images/', __FILE__ ) );
	wp_localize_script( 'dashboard_js1', 'plugin_data', $plugin_vals );
}

function fun_set_solr_options() {


	// Button Index
	if ( isset( $_POST['solr_index_data'] ) ) {

		$solr = new wp_Solr();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->index_data();

			if ( count( $val ) == 1 || $val == 1 ) {
				echo "<script type='text/javascript'>
                jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('success');
                });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('warning');
                });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_index_message').removeClass('loading');
               jQuery('.status_index_message').addClass('warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            });
            </script>";

		}

	}

	// Button delete
	if ( isset( $_POST['solr_delete_index'] ) ) {
		$solr = new wp_Solr();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->delete_documents();

			if ( $val == 0 ) {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
               jQuery('.status_del_message').addClass('success');
            });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
                              jQuery('.status_del_message').addClass('warning');
            });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
               jQuery('.status_del_message').addClass('warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            })
            </script>";
		}
	}


	?>
	<div class="wdm-wrap" xmlns="http://www.w3.org/1999/html">
		<div class="page_title"><h1>WPSOLR Settings </h1></div>

		<?php
		if ( isset ( $_GET['tab'] ) ) {
			wpsolr_admin_tabs( $_GET['tab'] );
		} else {
			wpsolr_admin_tabs( 'solr_config' );
		}

		if ( isset ( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		} else {
			$tab = 'solr_config';
		}

		switch ( $tab ) {
			case 'solr_config' :
				?>
				<div id="solr-configuration-tab">
					<div class='wrapper'>
						<h4 class='head_div'>Solr Configuration</h4>

						<div class="wdm_note">

							WPSOLR is compatible with the Solr versions listed at the following page: <a
								href="http://www.wpsolr.com/releases#1.0" target="__wpsolr">Compatible Solr versions</a>.

							Your first action must be to download the two configuration files (schema.xml,
							solrconfig.xml) listed in the online release section, and upload them to your Solr instance.
							Everything is described online.

						</div>
						<div class="wdm_row">
							<div class="submit">
								<a href='admin.php?page=solr_settings&tab=solr_hosting' class="button-primary wdm-save">I
									uploaded my 2 compatible configuration files to my Solr core >></a>
							</div>
						</div>
					</div>
				</div>
				<?php
				break;

			case 'solr_hosting' :
				?>

				<div id="solr-hosting-tab">
					<form action="options.php" method="POST" id='settings_conf_form'>

						<?php

						settings_fields( 'solr_conf_options' );


						$solr_options = get_option( 'wdm_solr_conf_data' );


						$solr_type = $solr_options['host_type'];


						?>

						<!--  <div class="wdm_heading wrapper"><h3>Configure Solr</h3></div>-->
						<input type='hidden' id='adm_path' value='<?php echo admin_url(); ?>'>

						<div class='wrapper'>
							<h4 class='head_div'>Solr Hosting Choice</h4>

							<div class="wdm_row">
								<div class='col_left'>Select Solr Hosting</div>
								<div class='col_right'>
									<input type='radio' name='wdm_solr_conf_data[host_type]' value='self_hosted'
									       class='radio_type' id='self_host'
										<?php if ( $solr_options['host_type'] == 'self_hosted' ) { ?> checked <?php } ?>

										> Self Hosted <br>
									<input type='radio' name='wdm_solr_conf_data[host_type]' value='other_hosted'
									       class='radio_type' id='other_host'
										<?php if ( $solr_options['host_type'] == 'other_hosted' ) { ?> checked <?php } ?>
										> Cloud Hosting
									(Click <a target='_blank'
									          href='http://www.wpsolr.com/solr-certified-hosting-providers'> here </a>
									to visit our certified Solr hosting providers)
									<br>
								</div>
								<div class="clear"></div>

							</div>

						</div>

						<div id='div_self_hosted' class="wrapper"
							<?php if ( $solr_type == 'self_hosted' ) {
								echo "style='display:block'";
							} else if ( $solr_type == 'other_hosted' ) {
								echo "style='display:none'";
							} else {
								echo "style='display:none'";
							} ?> >
							<h4 class='head_div'>Solr Hosting Settings</h4>

							<div class="wdm_note">

								<b> If your index url is:</b><br>
                                            <span style='margin-left:10px'>
                                               http://localhost:8983/solr/myindex/select 
                                            </span><br><br/>
								<b>Then your details will be </b><br>
								<span style='margin-left:10px'> <b>Protocol:</b> http</span><br>
								<span style='margin-left:10px'> <b>Host:</b> localhost</span><br>
								<span style='margin-left:10px'> <b>Port:</b> 8983 </span><br>
								<span style='margin-left:10px'>  <b> path:</b> /solr/myindex</span>

							</div>
							<div class="wdm_row">
								<div class='solr_error'></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Protocol</div>

								<div class='col_right'>

									<select name='wdm_solr_conf_data[solr_protocol]' id='solr_protocol'>
										<option value='http'
											<?php if ( $solr_options['solr_protocol'] == 'http' || $solr_options['solr_protocol'] == '' ) { ?>
												selected
											<?php } ?>
											>http
										</option>
										<option value='https'
											<?php if ( $solr_options['solr_protocol'] == 'https' ) { ?>
												selected
											<?php } ?>

											>https
										</option>
									</select>

									<span class='ghost_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Host</div>

								<div class='col_right'><input type='text' name='wdm_solr_conf_data[solr_host]'
								                              id='solr_host'
								                              value="<?php echo empty( $solr_options['solr_host'] ) ? 'localhost' : $solr_options['solr_host']; ?>">
									<span class='host_err'></span></div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Port</div>
								<div class='col_right'><input type='text' name='wdm_solr_conf_data[solr_port]'
								                              id='solr_port'
								                              value="<?php echo empty( $solr_options['solr_port'] ) ? '8983' : $solr_options['solr_port']; ?>">
									<span class='port_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Path</div>
								<div class='col_right'><input type='text' name='wdm_solr_conf_data[solr_path]'
								                              id='solr_path'
								                              value="<?php echo empty( $solr_options['solr_path'] ) ? '/solr' : $solr_options['solr_path']; ?>">
									<span class='path_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class='wdm_row'>
								<div class="submit">
									<!--<input name="save_selected_options" id='save_selected_options' type="submit" class="button-primary wdm-save" value="<?php //esc_attr_e('Save Changes');
									?>" />-->
									<input name="check_solr_status" id='check_solr_status' type="button"
									       class="button-primary wdm-save" value="Check Solr Status, Then Save"/>
                                            <span>
                                                <img
	                                                src='<?php echo plugins_url( 'images/gif-load_cir.gif', __FILE__ ) ?>'
	                                                style='height:18px;width:18px;margin-top: 10px;display: none'
	                                                class='img-load'/>
                                                <img src='<?php echo plugins_url( 'images/success.png', __FILE__ ) ?>'
                                                     style='height:18px;width:18px;margin-top: 10px;display: none'
                                                     class='img-succ'/>
                                                <img src='<?php echo plugins_url( 'images/warning.png', __FILE__ ) ?>'
                                                     style='height:18px;width:18px;margin-top: 10px;display: none'
                                                     class='img-err'/>
                                            </span>
								</div>
							</div>
							<div class="clear"></div>
						</div>

						<div id='hosted_on_other' class="wrapper" <?php if ( $solr_type == 'self_hosted' ) {
							echo "style='display:none'";
						} else if ( $solr_type == 'other_hosted' ) {
							echo "style='display:block'";
						} else {
							echo "style='display:none'";
						} ?>>
							<h4 class='head_div'>Solr Hosting Connection</h4>

							<div class='wdm_note'>
								<h4 class='head_div'>Solr hosting subscriptions</h4>

								<div class="wdm_row">
									<div class='col_left'><a href="http://www.gotosolr.com/en" target="__gotosolr">http://gotosolr.com</a>
										can provide a production ready Solr for WPSOLR.<br>
										WPSOLR is free, but you can buy and configure a Solr hosting subscription
										according to your needs.<br><br>
										Here is a <a href="http://www.gotosolr.com/en/solr-tutorial-for-wordpress"
										             target="_wpsolr-tutorial">tutorial</a> to setup WPSOLR with
										Gotosolr hosting
									</div>

									<div class='col_right'>
										<input name="gotosolr_plan_yearly_trial"
										       type="button" class="button-primary wdm-save"
										       value="Test one month with our yearly trial"
										       onclick="window.open('https://secure.avangate.com/order/trial.php?PRODS=4642999&amp;QTY=1&amp;PRICES4642999%5BEUR%5D=0&amp;TPERIOD=30&amp;PHASH=bb55c3bd6407e03a8b5fc91358347a4c', '__blank');"
											/>
										<input name="gotosolr_plan_yearly"
										       type="button" class="button-primary wdm-save"
										       value="Build your yearly plan with your own features"
										       onclick="window.open('https://secure.avangate.com/order/checkout.php?PRODS=4642999&QTY=1&CART=1&CARD=1', '__blank');"
											/>
										<input name="gotosolr_plan_monthly"
										       type="button" class="button-primary wdm-save"
										       value="Build your monthly plan with your own features"
										       onclick="window.open('https://secure.avangate.com/order/checkout.php?PRODS=4653966&QTY=1&CART=1&CARD=1', '__blank');"
											/>
									</div>
									<div class="clear"></div>

								</div>

							</div>

							<div class="wdm_note">

								<b> If your index url is:</b><br>
                                            <span style='margin-left:10px'> https://877d83f3-1055-4086-9fe6-cecd1b48411f-index.solrdata.com:8983/solr/e86f82a682564c23b7802b6827f3ccd4.24b7729e02dc47d19c15f1310098f93f/select
                                            </span><br><br/>
								<b>Then your details will be </b><br>
								<span style='margin-left:10px'> <b>Protocol:</b> https</span><br>
								<span style='margin-left:10px'> <b>Host:</b>  877d83f3-1055-4086-9fe6-cecd1b48411f-index.solrdata.com</span><br>
								<span style='margin-left:10px'> <b>Port:</b> 8983 </span><br>
								<span style='margin-left:10px'>  <b> path:</b> /solr/e86f82a682564c23b7802b6827f3ccd4.24b7729e02dc47d19c15f1310098f93f</span>

							</div>

							<div class="wdm_row">
								<div class='solr_error'></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Protocol</div>

								<div class='col_right'>


									<select name='wdm_solr_conf_data[solr_protocol_goto]' id='gtsolr_protocol'>
										<option value='http'
											<?php if ( $solr_options['solr_protocol_goto'] == 'http' ) { ?>
												selected
											<?php } ?>

											>http
										</option>
										<option value='https'
											<?php if ( $solr_options['solr_protocol_goto'] == 'https' || $solr_options['solr_protocol_goto'] == null ) { ?>
												selected
											<?php } ?>
											>https
										</option>

									</select>
									<span class='ghost_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Host</div>

								<div class='col_right'><input type='text' name='wdm_solr_conf_data[solr_host_goto]'
								                              id='gtsolr_host'
								                              value="<?php echo empty( $solr_options['solr_host_goto'] ) ? 'localhost' : $solr_options['solr_host_goto']; ?>">
									<span class='ghost_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Port</div>
								<div class='col_right'><input type='text' name='wdm_solr_conf_data[solr_port_goto]'
								                              id='gtsolr_port'
								                              value="<?php echo empty( $solr_options['solr_port_goto'] ) ? '8983' : $solr_options['solr_port_goto']; ?>">
									<span class='gport_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Solr Path</div>
								<div class='col_right'><input type='text' name='wdm_solr_conf_data[solr_path_goto]'
								                              id='gtsolr_path'
								                              value="<?php echo empty( $solr_options['solr_path_goto'] ) ? '/solr' : $solr_options['solr_path_goto']; ?>">
									<span class='gpath_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Key</div>
								<div class='col_right'>
									<input type='text' name='wdm_solr_conf_data[solr_key_goto]' id='gtsolr_key'
									       value="<?php echo empty( $solr_options['solr_key_goto'] ) ? '' : $solr_options['solr_key_goto']; ?>">
									<span class='gkey_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Secret</div>
								<div class='col_right'>
									<input type='text' name='wdm_solr_conf_data[solr_secret_goto]' id='gtsolr_secret'
									       value="<?php echo empty( $solr_options['solr_secret_goto'] ) ? '' : $solr_options['solr_secret_goto']; ?>">
									<span class='gsec_err'></span>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class="submit">
									<input name="check_solr_status_third" id='check_solr_status_third' type="button"
									       class="button-primary wdm-save" value="Check Solr Status, Then Save"/> <span><img
											src='<?php echo plugins_url( 'images/gif-load_cir.gif', __FILE__ ) ?>'
											style='height:18px;width:18px;margin-top: 10px;display: none'
											class='img-load'>
                                            
                                             <img src='<?php echo plugins_url( 'images/success.png', __FILE__ ) ?>'
                                                  style='height:18px;width:18px;margin-top: 10px;display: none'
                                                  class='img-succ'/>
                                                <img src='<?php echo plugins_url( 'images/warning.png', __FILE__ ) ?>'
                                                     style='height:18px;width:18px;margin-top: 10px;display: none'
                                                     class='img-err'/></span>
								</div>
							</div>
							<div class="clear"></div>
						</div>


					</form>
				</div>


				<?php
				break;
			case 'solr_option':
				?>
				<div id="solr-option-tab">

					<?php
					if ( isset ( $_GET['tab'] ) ) {
						if ( $_GET['tab'] == 'solr_option' ) {
							if ( isset ( $_GET['subtab'] ) ) {
								wpsolr_admin_sub_tabs( $_GET['subtab'] );
							} else {
								wpsolr_admin_sub_tabs( 'index_opt' );
							}
						}
					}

					if ( isset ( $_GET['subtab'] ) ) {
						$subtab = $_GET['subtab'];
					} else {
						$subtab = 'index_opt';
					}

					switch ( $subtab ) {
						case 'result_opt':


							?>
							<div id="solr-results-options" class="wdm-vertical-tabs-content">
								<form action="options.php" method="POST" id='res_settings_form'>
									<?php
									settings_fields( 'solr_res_options' );
									$solr_res_options = get_option( 'wdm_solr_res_data', array(
										'default_search' => 0,
										'res_info'       => '0',
										'spellchecker'   => '0'

									) );
									?>

									<div class='wrapper'>
										<h4 class='head_div'>Result Options</h4>

										<div class="wdm_note">

											In this section, you will choose how to display the results returned by a
											query to your Solr instance.

										</div>
										<div class="wdm_row">
											<div class='col_left'>Display suggestions (Did you mean?)</div>
											<div class='col_right'>
												<input type='checkbox'
												       name='wdm_solr_res_data[<?php echo 'spellchecker' ?>]'
												       value='spellchecker'
													<?php checked( 'spellchecker', $solr_res_options['spellchecker'] ); ?>>
											</div>
											<div class="clear"></div>
										</div>
										<div class="wdm_row">
											<div class='col_left'>Display number of results and current page</div>
											<div class='col_right'>
												<input type='checkbox' name='wdm_solr_res_data[res_info]'
												       value='res_info'
													<?php checked( 'res_info', $solr_res_options['res_info'] ); ?>>
											</div>
											<div class="clear"></div>
										</div>
										<div class="wdm_row">
											<div class='col_left'>Replace WordPress Default Search</div>
											<div class='col_right'>
												<input type='checkbox' name='wdm_solr_res_data[default_search]'
												       value='1'
													<?php checked( '1', $solr_res_options['default_search'] ); ?>>
											</div>
											<div class="clear"></div>
										</div>
										<div class="wdm_row">
											<div class='col_left'>No. of results per page</div>
											<div class='col_right'>
												<input type='text' id='number_of_res' name='wdm_solr_res_data[no_res]'
												       placeholder="Enter a Number"
												       value="<?php echo empty( $solr_res_options['no_res'] ) ? '20' : $solr_res_options['no_res']; ?>">
												<span class='res_err'></span><br>
											</div>
											<div class="clear"></div>
										</div>
										<div class="wdm_row">
											<div class='col_left'>No. of values to be displayed by facets</div>
											<div class='col_right'>
												<input type='text' id='number_of_fac' name='wdm_solr_res_data[no_fac]'
												       placeholder="Enter a Number"
												       value="<?php echo empty( $solr_res_options['no_fac'] ) ? '20' : $solr_res_options['no_fac']; ?>"><span
													class='fac_err'></span> <br>
											</div>
											<div class="clear"></div>
										</div>
										<div class='wdm_row'>
											<div class="submit">
												<input name="save_selected_options_res_form"
												       id="save_selected_res_options_form" type="submit"
												       class="button-primary wdm-save" value="Save Options"/>


											</div>
										</div>
									</div>

								</form>
							</div>
							<?php
							break;
						case 'index_opt':


							$posts                     = get_post_types();
							$args       = array(
								'public'   => true,
								'_builtin' => false

							);
							$output     = 'names'; // or objects
							$operator   = 'and'; // 'and' or 'or'
							$taxonomies = get_taxonomies( $args, $output, $operator );
							global $wpdb;
							$limit      = (int) apply_filters( 'postmeta_form_limit', 30 );
							$keys       = $wpdb->get_col( "
                                                                    SELECT meta_key
                                                                    FROM $wpdb->postmeta
                                                                    WHERE meta_key!='bwps_enable_ssl' 
                                                                    GROUP BY meta_key
                                                                    HAVING meta_key NOT LIKE '\_%'
                                                                    ORDER BY meta_key" );
							$post_types = array();
							foreach ( $posts as $ps ) {
								if ( $ps != 'attachment' && $ps != 'revision' && $ps != 'nav_menu_item' ) {
									array_push( $post_types, $ps );
								}
							}

							$allowed_attachments_types = get_allowed_mime_types();

							?>
							<div id="solr-indexing-options" class="wdm-vertical-tabs-content">
								<form action="options.php" method="POST" id='settings_form'>
									<?php
									settings_fields( 'solr_form_options' );
									$solr_options = get_option( 'wdm_solr_form_data', array(
										'comments'         => 0,
										'p_types'          => '',
										'taxonomies'       => '',
										'cust_fields'      => '',
										'attachment_types' => ''
									) );
									?>


									<div class='indexing_option wrapper'>
										<h4 class='head_div'>Indexing Options</h4>

										<div class="wdm_note">

											In this section, you will choose among all the data stored in your Wordpress
											site, which you want to load in your Solr index.

										</div>

										<div class="wdm_row">
											<div class='col_left'>Post types to be indexed</div>
											<div class='col_right'>
												<input type='hidden' name='wdm_solr_form_data[p_types]' id='p_types'>
												<?php
												$post_types_opt = $solr_options['p_types'];
												foreach ( $post_types as $type ) {
													?>
													<input type='checkbox' name='post_tys' value='<?php echo $type ?>'
														<?php if ( strpos( $post_types_opt, $type ) !== false ) { ?> checked <?php } ?>> <?php echo $type ?>
													<br>
													<?php
												}
												?>

											</div>
											<div class="clear"></div>
										</div>

										<div class="wdm_row">
											<div class='col_left'>Attachment types to be indexed</div>
											<div class='col_right'>
												<input type='hidden' name='wdm_solr_form_data[attachment_types]'
												       id='attachment_types'>
												<?php
												$attachment_types_opt = $solr_options['attachment_types'];
												foreach ( $allowed_attachments_types as $type ) {
													?>
													<input type='checkbox' name='attachment_types'
													       value='<?php echo $type ?>'
														<?php if ( strpos( $attachment_types_opt, $type ) !== false ) { ?> checked <?php } ?>> <?php echo $type ?>
													<br>
													<?php
												}
												?>
											</div>
											<div class="clear"></div>
										</div>

										<div class="wdm_row">
											<div class='col_left'>Custom taxonomies to be indexed</div>
											<div class='col_right'>
												<div class='cust_tax'><!--new div class given-->
													<input type='hidden' name='wdm_solr_form_data[taxonomies]'
													       id='tax_types'>
													<?php
													$tax_types_opt = $solr_options['taxonomies'];
													if ( count( $taxonomies ) > 0 ) {
														foreach ( $taxonomies as $type ) {
															?>

															<input type='checkbox' name='taxon'
															       value='<?php echo $type . "_str" ?>'
																<?php if ( strpos( $tax_types_opt, $type . "_str" ) !== false ) { ?> checked <?php } ?>
																> <?php echo $type ?> <br>
															<?php
														}

													} else {
														echo 'None';
													} ?>
												</div>
											</div>
											<div class="clear"></div>
										</div>

										<div class="wdm_row">
											<div class='col_left'>Custom Fields to be indexed</div>

											<div class='col_right'>
												<input type='hidden' name='wdm_solr_form_data[cust_fields]'
												       id='field_types'>

												<div class='cust_fields'><!--new div class given-->
													<?php
													$field_types_opt = $solr_options['cust_fields'];
													if ( count( $keys ) > 0 ) {
														foreach ( $keys as $key ) {
															?>

															<input type='checkbox' name='cust_fields'
															       value='<?php echo $key . "_str" ?>'
																<?php if ( strpos( $field_types_opt, $key . "_str" ) !== false ) { ?> checked <?php } ?>> <?php echo $key ?>
															<br>
															<?php
														}

													} else {
														echo 'None';
													}
													?>
												</div>
											</div>
											<div class="clear"></div>
										</div>

										<div class="wdm_row">
											<div class='col_left'>Index Comments</div>
											<div class='col_right'>
												<input type='checkbox' name='wdm_solr_form_data[comments]'
												       value='1' <?php checked( '1', $solr_options['comments'] ); ?>>

											</div>
											<div class="clear"></div>
										</div>
										<div class="wdm_row">
											<div class='col_left'>Exclude items (Posts,Pages,...)</div>
											<div class='col_right'>
												<input type='text' name='wdm_solr_form_data[exclude_ids]'
												       placeholder="Comma separated ID's list"
												       value="<?php echo empty( $solr_options['exclude_ids'] ) ? '' : $solr_options['exclude_ids']; ?>">
												<br>
												(Comma separated ids list)
											</div>
											<div class="clear"></div>
										</div>
										<div class='wdm_row'>
											<div class="submit">
												<input name="save_selected_index_options_form"
												       id="save_selected_index_options_form" type="submit"
												       class="button-primary wdm-save" value="Save Options"/>


											</div>
										</div>

									</div>
								</form>
							</div>
							<?php
							break;

						case 'facet_opt':
							$solr_options   = get_option( 'wdm_solr_form_data' );
							$checked_fls = $solr_options['cust_fields'] . ',' . $solr_options['taxonomies'];

							$checked_fields = array();
							$checked_fields = explode( ',', $checked_fls );
							$img_path       = plugins_url( 'images/plus.png', __FILE__ );
							$minus_path     = plugins_url( 'images/minus.png', __FILE__ );
							$built_in       = array( 'Type', 'Author', 'Categories', 'Tags' );
							$built_in       = array_merge( $built_in, $checked_fields );
							?>
							<div id="solr-facets-options" class="wdm-vertical-tabs-content">
								<form action="options.php" method="POST" id='fac_settings_form'>
									<?php
									settings_fields( 'solr_facet_options' );
									$solr_fac_options      = get_option( 'wdm_solr_facet_data' );
									$selected_facets_value = $solr_fac_options['facets'];
									if ( $selected_facets_value != '' ) {
										$selected_array = explode( ',', $selected_facets_value );
									} else {
										$selected_array = array();
									}
									?>
									<div class='wrapper'>
										<h4 class='head_div'>Facets Options</h4>

										<div class="wdm_note">

											In this section, you will choose which data you want to display as facets in
											your search results. Facets are extra filters usually seen in the left hand
											side of the results, displayed as a list of links. You can add facets only
											to data you've selected to be indexed.

										</div>
										<div class="wdm_note">
											<h4>Instructions</h4>
											<ul class="wdm_ul wdm-instructions">
												<li>Click on the 'Plus' icon to add the facets</li>
												<li>Click on the 'Minus' icon to remove the facets</li>
												<li>Sort the items in the order you want to display them by dragging and
													dropping them at the desired plcae
												</li>
											</ul>
										</div>

										<div class="wdm_row">
											<div class='avail_fac'>
												<h4>Available items for facets</h4>
												<input type='hidden' id='checked_options' name='checked_options'
												       value='<?php echo $checked_fls ?>'>
												<input type='hidden' id='select_fac' name='wdm_solr_facet_data[facets]'
												       value='<?php echo $selected_facets_value ?>'>


												<ul id="sortable1" class="wdm_ul connectedSortable">
													<?php
													if ( $selected_facets_value != '' ) {
														foreach ( $selected_array as $selected_val ) {
															if ( $selected_val != '' ) {
																if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == "_str" ) {
																	$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
																} else {
																	$dis_text = $selected_val;
																}


																echo "<li id='$selected_val' class='ui-state-default facets facet_selected'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:none'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:inline' title='Click to Remove the Facet'></li>";
															}
														}
													}
													foreach ( $built_in as $built_fac ) {
														if ( $built_fac != '' ) {
															$buil_fac = strtolower( $built_fac );
															if ( substr( $buil_fac, ( strlen( $buil_fac ) - 4 ), strlen( $buil_fac ) ) == "_str" ) {
																$dis_text = substr( $buil_fac, 0, ( strlen( $buil_fac ) - 4 ) );
															} else {
																$dis_text = $buil_fac;
															}

															if ( ! in_array( $buil_fac, $selected_array ) ) {

																echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:inline' title='Click to Add the Facet'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:none'></li>";
															}
														}
													}
													?>


												</ul>
											</div>

											<div class="clear"></div>
										</div>

										<div class='wdm_row'>
											<div class="submit">
												<input name="save_facets_options_form" id="save_facets_options_form"
												       type="submit" class="button-primary wdm-save"
												       value="Save Options"/>


											</div>
										</div>
									</div>
								</form>
							</div>
							<?php
							break;

						case 'sort_opt':
							$img_path    = plugins_url( 'images/plus.png', __FILE__ );
							$minus_path = plugins_url( 'images/minus.png', __FILE__ );

							$checked_fls = array();
							$built_in    = wp_Solr::get_sort_options();

							?>
							<div id="solr-sort-options" class="wdm-vertical-tabs-content">
								<form action="options.php" method="POST" id='sort_settings_form'>
									<?php
									settings_fields( 'solr_sort_options' );
									$solr_sort_options   = get_option( 'wdm_solr_sortby_data' );
									$selected_sort_value = $solr_sort_options['sort'];
									if ( $selected_sort_value != '' ) {
										$selected_array = explode( ',', $selected_sort_value );
									} else {
										$selected_array = array();
									}
									?>
									<div class='wrapper'>
										<h4 class='head_div'>Sort Options</h4>

										<div class="wdm_note">

											In this section, you will choose which elements will be displayed as sort
											criteria for your search results, and in which order.

										</div>
										<div class="wdm_note">
											<h4>Instructions</h4>
											<ul class="wdm_ul wdm-instructions">
												<li>Click on the 'Plus' icon to add the sort</li>
												<li>Click on the 'Minus' icon to remove the sort</li>
												<li>Sort the items in the order you want to display them by dragging and
													dropping them at the desired place
												</li>
											</ul>
										</div>

										<div class="wdm_row">
											<div class='col_left'>Default when no sort is selected by the user</div>
											<div class='col_right'>
												<select name="wdm_solr_sortby_data[sort_default]">
													<?php foreach ( $built_in as $sort ) {
														$selected = $solr_sort_options['sort_default'] == $sort['code'] ? 'selected' : '';
														?>
														<option
															value="<?php echo $sort['code'] ?>" <?php echo $selected ?> ><?php echo $sort['label'] ?></option>
													<?php } ?>
												</select>
											</div>
										</div>

										<div class="wdm_row">
											<div class='avail_fac'>
												<h4>Activate/deactivate items in the sort list</h4>
												<input type='hidden' id='checked_options' name='checked_options'
												       value='<?php echo $checked_fls ?>'>
												<input type='hidden' id='select_sort' name='wdm_solr_sortby_data[sort]'
												       value='<?php echo $selected_sort_value ?>'>


												<ul id="sortable_sort" class="wdm_ul connectedSortable_sort">
													<?php
													if ( $selected_sort_value != '' ) {
														foreach ( $selected_array as $sort_code ) {
															if ( $sort_code != '' ) {
																$sort     = wp_Solr::get_sort_option_from_code( $sort_code, null );
																$dis_text = is_array( $sort ) ? $sort['label'] : $sort_code;

																echo "<li id='$sort_code' class='ui-state-default facets sort_selected'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon_sort' style='display:none'>
                                                                                                <img src='$minus_path' class='minus_icon_sort' style='display:inline' title='Click to Remove the Sort'></li>";
															}
														}
													}
													foreach ( $built_in as $built ) {
														if ( $built != '' ) {
															$buil_fac = $built[ code ];
															$dis_text = $built[ label ];

															if ( ! in_array( $buil_fac, $selected_array ) ) {

																echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon_sort' style='display:inline' title='Click to Add the Sort'>
                                                                                                <img src='$minus_path' class='minus_icon_sort' style='display:none'></li>";
															}
														}
													}
													?>


												</ul>
											</div>

											<div class="clear"></div>
										</div>

										<div class='wdm_row'>
											<div class="submit">
												<input name="save_sort_options_form" id="save_sort_options_form"
												       type="submit" class="button-primary wdm-save"
												       value="Save Options"/>


											</div>
										</div>
									</div>
								</form>
							</div>
							<?php
							break;

						case 'extension_groups_opt':
							// Include the options form
							WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GROUPS );
							break;
						case 'extension_s2member_opt':
							// Include the options form
							WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_S2MEMBER );
							break;
					}

					?>

				</div>
				<?php
				break;

			case 'solr_operations':

				$solr                             = new wp_Solr();
				$count_nb_documents_to_be_indexed = $solr->count_nb_documents_to_be_indexed();

				?>

				<div id="solr-operations-tab">
					<form action="options.php" method='post' id='solr_actions'>
						<?php

						settings_fields( 'solr_operations_options' );

						$solr_operations_options = get_option( 'wdm_solr_operations_data' );

						$batch_size = empty( $solr_operations_options['batch_size'] ) ? '100' : $solr_operations_options['batch_size'];

						?>
						<input type='hidden' id='adm_path' value='<?php echo admin_url(); ?>'> <!-- for ajax -->
						<div class='wrapper'>
							<h4 class='head_div'>Solr Operations</h4>

							<div class="wdm_note">
								<div>
									<?php
									try {
										$nb_documents_in_index = $solr->get_count_documents();
										echo "<b>A total of $nb_documents_in_index documents are currently in your index</b>";
									} catch ( Exception $e ) {
										echo '<b>Please check your Solr Hosting, an exception occured while calling your Solr server:</b> <br><br>' . htmlentities( $e->getMessage() );
									}
									?>
								</div>
								<?php if ( $count_nb_documents_to_be_indexed >= 0 ): ?>
									<div><b>
											<?php
											echo $count_nb_documents_to_be_indexed;

											// Reset value so it's not displayed next time this page is displayed.
											//$solr->update_count_documents_indexed_last_operation();
											?>
										</b> document(s) remain to be indexed
									</div>
								<?php endif ?>
							</div>
							<div class="wdm_row">
								<p>The indexing is <b>incremental</b>: only documents updated after the last operation
									are sent to the index.</p>

								<p>So, the first operation will index all documents, by batches of
									<b><?php echo $batch_size; ?></b> documents.</p>

								<p>If a <b>timeout</b> occurs, you just have to click on the button again: the process
									will restart from where it stopped.</p>

								<p>If you need to reindex all again, delete the index first.</p>
							</div>
							<div class="wdm_row">
								<div class='col_left'>Number of documents sent in Solr as a single commit.<br>
									You can change this number to control indexing's performance.
								</div>
								<div class='col_right'>
									<input type='text' id='batch_size' name='wdm_solr_operations_data[batch_size]'
									       placeholder="Enter a Number"
									       value="<?php echo $batch_size; ?>">
									<span class='res_err'></span><br>
								</div>
								<div class="clear"></div>
								<div class='col_left'>Display debug infos during indexing</div>
								<div class='col_right'>

									<input type='checkbox'
									       id='is_debug_indexing'
									       name='wdm_solr_operations_data[is_debug_indexing]'
									       value='is_debug_indexing'
										<?php checked( 'is_debug_indexing', $solr_operations_options['is_debug_indexing'] ); ?>>
									<span class='res_err'></span><br>
								</div>
								<div class="clear"></div>
							</div>
							<div class="wdm_row">
								<div class="submit">
									<input name="solr_start_index_data" type="submit" class="button-primary wdm-save"
									       id='solr_start_index_data'
									       value="Synchronize Wordpress with my Solr index"/>
									<input name="solr_stop_index_data" type="submit" class="button-primary wdm-save"
									       id='solr_stop_index_data' value="Stop current indexing"
									       style="visibility: hidden;"/>
									<span class='status_index_icon'></span>

									<input name="solr_delete_index" type="submit" class="button-primary wdm-save"
									       id="solr_delete_index"
									       value="Empty the Solr index"/>


									<span class='status_index_message'></span>
									<span class='status_debug_message'></span>
									<span class='status_del_message'></span>
								</div>
							</div>
						</div>
					</form>
				</div>
				<?php
				break;


		}

		?>


	</div>
	<?php


}

function wpsolr_admin_tabs( $current = 'solr_config' ) {
	$tabs = array(
		'solr_config'     => 'Solr Configuration',
		'solr_hosting'    => 'Solr Hosting',
		'solr_option'     => 'Solr Options',
		'solr_operations' => 'Solr Operations'
	);
	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab'>$name</a>";

	}
	echo '</h2>';
}


function wpsolr_admin_sub_tabs( $current = 'index_opt' ) {
	$tab     = $_GET['tab'];
	$subtabs = array(
		'index_opt'              => 'Indexing Options',
		'result_opt'             => 'Result Options',
		'facet_opt'              => 'Facets Options',
		'sort_opt'               => 'Sort Options',
		'extension_groups_opt'   => 'Groups plugin options',
		'extension_s2member_opt' => 's2Member plugin options'
	);
	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wdm-vertical-tabs">';
	foreach ( $subtabs as $subtab => $name ) {
		$class = ( $subtab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab&subtab=$subtab'>$name</a>";

	}
	echo '</h2>';
}

