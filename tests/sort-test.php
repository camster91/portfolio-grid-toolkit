<?php
/**
 * Contract tests for the global portfolio sort endpoint.
 */

define( 'ABSPATH', __DIR__ );

$pgtk_test_published_ids = array( 1, 2, 3 );
$pgtk_test_denied_ids    = array();
$pgtk_test_updates       = array();

function add_action() {}
function add_submenu_page() {}
function is_admin() { return true; }
function check_ajax_referer() { return true; }
function wp_unslash( $value ) { return $value; }
function sanitize_text_field( $value ) { return (string) $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function sanitize_title( $value ) { return preg_replace( '/[^a-z0-9-]+/', '-', strtolower( trim( (string) $value ) ) ); }
function wp_parse_id_list( $value ) {
    return array_values( array_filter( array_map( 'intval', explode( ',', (string) $value ) ) ) );
}
function get_posts() {
    global $pgtk_test_published_ids;
    return $pgtk_test_published_ids;
}
function current_user_can( $capability, $post_id = 0 ) {
    global $pgtk_test_denied_ids;
    if ( 'edit_others_posts' === $capability ) {
        return true;
    }
    return 'edit_post' !== $capability || ! in_array( (int) $post_id, $pgtk_test_denied_ids, true );
}
function wp_update_post( $post ) {
    global $pgtk_test_updates;
    $pgtk_test_updates[] = $post;
    return $post['ID'];
}
function is_wp_error() { return false; }
function wp_send_json_error( $data, $status = 400 ) {
    throw new RuntimeException( 'error:' . $data['message'] . ':' . $status );
}
function wp_send_json_success( $data ) {
    throw new RuntimeException( 'success:' . $data['updated'] . ':' . $data['total'] );
}

require dirname( __DIR__ ) . '/includes/sort.php';

$scoped_context = pgtk_sort_context_from_request(
    array(
        'post_status' => 'draft',
        'collection'  => 'Campaigns',
        'artist'      => 'Creator',
    )
);
if ( array( 'post_status' => 'draft', 'collection' => 'campaigns', 'artist' => 'creator' ) !== $scoped_context ) {
    fwrite( STDERR, "Sort context was not normalized.\n" );
    exit( 1 );
}

$invalid_context = pgtk_sort_context_from_request(
    array(
        'post_status' => array( 'draft' ),
        'collection'  => array( 'campaigns' ),
        'artist'      => array( 'creator' ),
    )
);
if ( array( 'post_status' => 'publish', 'collection' => '', 'artist' => '' ) !== $invalid_context ) {
    fwrite( STDERR, "Sort context did not reject non-scalar request values.\n" );
    exit( 1 );
}

$scoped_args = pgtk_sort_query_args( $scoped_context, 'ids' );
if ( 'draft' !== $scoped_args['post_status'] || 'ids' !== $scoped_args['fields'] || 'AND' !== $scoped_args['tax_query']['relation'] ) {
    fwrite( STDERR, "Scoped sort query is incomplete.\n" );
    exit( 1 );
}
if ( 'campaigns' !== $scoped_args['tax_query'][0]['terms'] || 'creator' !== $scoped_args['tax_query'][1]['terms'] ) {
    fwrite( STDERR, "Scoped sort query did not retain collection and artist filters.\n" );
    exit( 1 );
}

function pgtk_expect_sort_result( $order, $expected ) {
    $_POST['order'] = $order;
    try {
        pgtk_sort_save_order();
    } catch ( RuntimeException $exception ) {
        if ( $expected === $exception->getMessage() ) {
            return;
        }
        fwrite( STDERR, 'Expected ' . $expected . ', got ' . $exception->getMessage() . ".\n" );
        exit( 1 );
    }
    fwrite( STDERR, 'Expected endpoint response ' . $expected . ".\n" );
    exit( 1 );
}

pgtk_expect_sort_result( '3,2', 'error:stale_or_invalid_order:409' );

$pgtk_test_denied_ids = array( 2 );
pgtk_expect_sort_result( '3,2,1', 'error:forbidden_post:403' );

$pgtk_test_denied_ids = array();
$pgtk_test_updates    = array();
$_POST['post_status']      = 'draft';
$_POST['collection']       = 'campaigns';
$_POST['artist']           = 'creator';
pgtk_expect_sort_result( '3,2,1', 'success:3:3' );

$expected_updates = array(
    array( 'ID' => 3, 'menu_order' => 3 ),
    array( 'ID' => 2, 'menu_order' => 2 ),
    array( 'ID' => 1, 'menu_order' => 1 ),
);
if ( $expected_updates !== $pgtk_test_updates ) {
    fwrite( STDERR, "Sort order updates did not match the submitted complete set.\n" );
    exit( 1 );
}

echo "Portfolio sort endpoint contract passed.\n";
