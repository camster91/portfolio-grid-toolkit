<?php
/**
 * Verify that the generic plugin loads only its own grid assets.
 */

define( 'ABSPATH', __DIR__ );

$ashbpg_test_singular = false;
$ashbpg_test_content  = '';
$ashbpg_test_elementor = '';
$ashbpg_test_enqueued = array();
$ashbpg_test_registered = array();

function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url() { return '/wp-content/plugins/ashbi-portfolio-grid/'; }
function plugin_basename( $file ) { return basename( $file ); }
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function is_singular() { global $ashbpg_test_singular; return $ashbpg_test_singular; }
function get_queried_object_id() { return 123; }
function get_post() { global $ashbpg_test_content; return (object) array( 'post_content' => $ashbpg_test_content ); }
function get_post_meta() { global $ashbpg_test_elementor; return $ashbpg_test_elementor; }
function wp_register_style( $handle ) { global $ashbpg_test_registered; $ashbpg_test_registered[] = $handle; }
function wp_register_script( $handle ) { global $ashbpg_test_registered; $ashbpg_test_registered[] = $handle; }
function wp_enqueue_style( $handle ) { global $ashbpg_test_enqueued; $ashbpg_test_enqueued[] = $handle; }
function wp_enqueue_script( $handle ) { global $ashbpg_test_enqueued; $ashbpg_test_enqueued[] = $handle; }

require dirname( __DIR__ ) . '/ashbi-portfolio-grid.php';

ashbpg_register_frontend_assets();
if ( array( 'ashbpg-ui', 'ashbpg-style', 'ashbpg-script' ) !== $ashbpg_test_registered ) {
    fwrite( STDERR, "Only grid assets should be registered.\n" );
    exit( 1 );
}

ashbpg_enqueue_current_page_assets();
if ( $ashbpg_test_enqueued ) {
    fwrite( STDERR, "Assets loaded outside a singular page.\n" );
    exit( 1 );
}

$ashbpg_test_singular = true;
$ashbpg_test_content  = 'Ordinary page content';
ashbpg_enqueue_current_page_assets();
if ( $ashbpg_test_enqueued ) {
    fwrite( STDERR, "Assets loaded without the shortcode.\n" );
    exit( 1 );
}

$ashbpg_test_content = '[ashbi_portfolio collection="campaigns"]';
ashbpg_enqueue_current_page_assets();
if ( array( 'ashbpg-style', 'ashbpg-script' ) !== $ashbpg_test_enqueued ) {
    fwrite( STDERR, "Classic shortcode assets did not load.\n" );
    exit( 1 );
}

$ashbpg_test_enqueued = array();
$ashbpg_test_content  = '';
$ashbpg_test_elementor = '[pgtk_work artist=\\"creator\\"]';
ashbpg_enqueue_current_page_assets();
if ( array( 'ashbpg-style', 'ashbpg-script' ) !== $ashbpg_test_enqueued ) {
    fwrite( STDERR, "Elementor shortcode assets did not load.\n" );
    exit( 1 );
}

echo "Portfolio asset loading contract passed.\n";
