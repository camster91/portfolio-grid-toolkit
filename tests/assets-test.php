<?php
/**
 * Verify that the generic plugin loads only its own grid assets.
 */

define( 'ABSPATH', __DIR__ );

$pgtk_test_singular = false;
$pgtk_test_content  = '';
$pgtk_test_elementor = '';
$pgtk_test_enqueued = array();
$pgtk_test_registered = array();

function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url() { return '/wp-content/plugins/portfolio-grid-toolkit/'; }
function plugin_basename( $file ) { return basename( $file ); }
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function is_singular() { global $pgtk_test_singular; return $pgtk_test_singular; }
function get_queried_object_id() { return 123; }
function get_post() { global $pgtk_test_content; return (object) array( 'post_content' => $pgtk_test_content ); }
function get_post_meta() { global $pgtk_test_elementor; return $pgtk_test_elementor; }
function wp_register_style( $handle ) { global $pgtk_test_registered; $pgtk_test_registered[] = $handle; }
function wp_register_script( $handle ) { global $pgtk_test_registered; $pgtk_test_registered[] = $handle; }
function wp_enqueue_style( $handle ) { global $pgtk_test_enqueued; $pgtk_test_enqueued[] = $handle; }
function wp_enqueue_script( $handle ) { global $pgtk_test_enqueued; $pgtk_test_enqueued[] = $handle; }

require dirname( __DIR__ ) . '/portfolio-grid-toolkit.php';

pgtk_register_frontend_assets();
if ( array( 'pgtk-style', 'pgtk-script' ) !== $pgtk_test_registered ) {
    fwrite( STDERR, "Only grid assets should be registered.\n" );
    exit( 1 );
}

pgtk_enqueue_current_page_assets();
if ( $pgtk_test_enqueued ) {
    fwrite( STDERR, "Assets loaded outside a singular page.\n" );
    exit( 1 );
}

$pgtk_test_singular = true;
$pgtk_test_content  = 'Ordinary page content';
pgtk_enqueue_current_page_assets();
if ( $pgtk_test_enqueued ) {
    fwrite( STDERR, "Assets loaded without the shortcode.\n" );
    exit( 1 );
}

$pgtk_test_content = '[pgtk_work collection="campaigns"]';
pgtk_enqueue_current_page_assets();
if ( array( 'pgtk-style', 'pgtk-script' ) !== $pgtk_test_enqueued ) {
    fwrite( STDERR, "Classic shortcode assets did not load.\n" );
    exit( 1 );
}

$pgtk_test_enqueued = array();
$pgtk_test_content  = '';
$pgtk_test_elementor = '[pgtk_work artist=\\"creator\\"]';
pgtk_enqueue_current_page_assets();
if ( array( 'pgtk-style', 'pgtk-script' ) !== $pgtk_test_enqueued ) {
    fwrite( STDERR, "Elementor shortcode assets did not load.\n" );
    exit( 1 );
}

echo "Portfolio asset loading contract passed.\n";
