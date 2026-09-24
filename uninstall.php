<?php
/**
 * Preserve portfolio records and metadata when this plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Projects, taxonomy terms, and credits are site content, not temporary state.
