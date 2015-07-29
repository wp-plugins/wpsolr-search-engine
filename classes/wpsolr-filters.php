<?php

/**
 * Base class for filters definitions.
 *
 * Developers: try to use these constants in your filters.
 */
class WpSolrFilters {

	// Add 'groups' plugin infos to a Solr results document
	const WPSOLR_FILTER_SOLR_RESULTS_DOCUMENT_GROUPS_INFOS = 'wpsolr_filter_solr_results_document_groups_infos';

	// Customize a post custom fields before they are processed in a Solarium update document
	const WPSOLR_FILTER_POST_CUSTOM_FIELDS = 'wpsolr_filter_post_custom_fields';

	// Customize a fully processed Solarium update document before sending to Solr for indexing
	const WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE = 'wpsolr_filter_solarium_document_for_update';

	// Customize a fully processed attachment content before sending to Solr for indexing
	const WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA = 'wpsolr_filter_attachment_text_extracted_by_apache_tika';
}