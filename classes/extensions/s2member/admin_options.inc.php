<?php

/**
 * Included file to display admin options
 */


WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_S2MEMBER, true );


WpSolrS2Member::update_custom_field_capabilities( WpSolrS2Member::CUSTOM_FIELD_NAME_STORING_POST_CAPABILITIES );

$extension_options_name = 'wdm_solr_extension_s2member_data';
$settings_fields_name   = 'solr_extension_s2member_options';

$array_extension_options             = get_option( $extension_options_name );
$is_plugin_active                    = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_S2MEMBER );
$is_plugin_custom_field_for_indexing = WpSolrS2Member::get_custom_field_capabilities( WpSolrS2Member::CUSTOM_FIELD_NAME_STORING_POST_CAPABILITIES );
$custom_field_for_indexing_name = WpSolrS2Member::CUSTOM_FIELD_NAME_STORING_POST_CAPABILITIES
?>

	<div id="extension_s2member-options" class="wdm-vertical-tabs-content">
		<form action="options.php" method="POST" id='extension_s2member_settings_form'>
			<?php
			settings_fields( $settings_fields_name );
			$solr_extension_s2member_options = get_option( $extension_options_name, array(
				'is_extension_active'                                => '0',
				'is_users_without_capabilities_see_all_results'      => '0',
				'is_result_without_capabilities_seen_by_all_users'   => '0',
				'message_user_without_capabilities_shown_no_results' => '',
				'message_result_capability_matches_user_group'       => ''
			) );
			?>

			<div class='wrapper'>
				<h4 class='head_div'>s2Member plugin Options</h4>

				<div class="wdm_note">

					In this section, you will configure how to restrict Solr search with levels
					and capabilities.<br/>

					<?php if ( ! $is_plugin_active ): ?>
						<p>
							Status: <a href="https://wordpress.org/plugins/s2member/"
							           target="_blank">s2Member
								plugin</a> is not activated. First, you need to install and
							activate it to configure WPSOLR.
						</p>
						<p>
							You will also need to re-index all your data if you activated
							<a href="https://wordpress.org/plugins/s2member/"
							   target="_blank">s2Member
								plugin</a>
							after you activated WPSOLR.
						</p>
					<?php else: ?>
						<p>
							Status: <a href="https://wordpress.org/plugins/s2member/"
							           target="_blank">s2Member
								plugin</a>
							is activated. You can now configure WPSOLR to use it.
						</p>
					<?php endif; ?>
					<?php if ( ( ! $is_plugin_custom_field_for_indexing ) && ( isset( $array_extension_options['is_extension_active'] ) ) ): ?>
						<p>
							The custom field <b>'<?php echo $custom_field_for_indexing_name ?>
								'</b>
							is not selected,
							which means WPSOLR will not be able to index data from <a
								href="https://wordpress.org/plugins/s2member/"
								target="_blank">s2Member
								plugin</a>.
							<br/>Please go to 'Indexing options' tab, and check
							<b>'<?php echo $custom_field_for_indexing_name ?>'</b>.
							<br/>You should also better re-index your data.
						</p>
					<?php endif; ?>

				</div>
				<div class="wdm_row">
					<div class='col_left'>Use the <a
							href="https://wordpress.org/plugins/s2member/"
							target="_blank">s2Member
							plugin</a>
						to filter search results.
						<br/>Think of re-indexing all your data if <a
							href="https://wordpress.org/plugins/s2member/"
							target="_blank">s2Member
							plugin</a> was installed after WPSOLR.
					</div>
					<div class='col_right'>
						<input type='checkbox'
						       name='wdm_solr_extension_s2member_data[is_extension_active]'
						       value='is_extension_active'
							<?php checked( 'is_extension_active', $solr_extension_s2member_options['is_extension_active'] ); ?>>
					</div>
					<div class="clear"></div>
				</div>
				<div class="wdm_row">
					<div class='col_left'>Users without levels/custom capabilities can see all results, <br/>
						whatever the results levels/custom capabilities.
					</div>
					<div class='col_right'>
						<input type='checkbox'
						       name='wdm_solr_extension_s2member_data[is_users_without_capabilities_see_all_results]'
						       value='is_users_without_capabilities_see_all_results'
							<?php checked( 'is_users_without_capabilities_see_all_results', $solr_extension_s2member_options['is_users_without_capabilities_see_all_results'] ); ?>>
					</div>
					<div class="clear"></div>
				</div>
				<div class="wdm_row">
					<div class='col_left'>Results without level/custom capabilities can be seen by all users,
						<br/> whatever the users levels/custom capabilities.
					</div>
					<div class='col_right'>
						<input type='checkbox'
						       name='wdm_solr_extension_s2member_data[is_result_without_capabilities_seen_by_all_users]'
						       value='is_result_without_capabilities_seen_by_all_users'
							<?php checked( 'is_result_without_capabilities_seen_by_all_users', $solr_extension_s2member_options['is_result_without_capabilities_seen_by_all_users'] ); ?>>
					</div>
					<div class="clear"></div>
				</div>
				<div class="wdm_row">
					<div class='col_left'>Phrase to display if you forbid users without levels/custom capabilities
						to see any results
					</div>
					<div class='col_right'>
												<textarea id='message_user_without_capabilities_shown_no_results'
												          name='wdm_solr_extension_s2member_data[message_user_without_capabilities_shown_no_results]'
												          rows="4" cols="100"
												          placeholder="<?php echo WpSolrS2Member::DEFAULT_MESSAGE_NOT_AUTHORIZED; ?>"><?php echo empty( $solr_extension_s2member_options['message_user_without_capabilities_shown_no_results'] ) ? trim( WpSolrS2Member::DEFAULT_MESSAGE_NOT_AUTHORIZED ) : $solr_extension_s2member_options['message_user_without_capabilities_shown_no_results']; ?></textarea>
						<span class='res_err'></span><br>
					</div>
					<div class="clear"></div>
				</div>
				<div class='wdm_row'>
					<div class="submit">
						<input name="save_selected_options_res_form"
						       id="save_selected_extension_s2member_form" type="submit"
						       class="button-primary wdm-save" value="Save Options"/>


					</div>
				</div>
			</div>

		</form>
	</div>