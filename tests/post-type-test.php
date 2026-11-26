<?php
/**
 * Minimal contract test for the plugin-owned portfolio content model.
 */

define( 'ABSPATH', __DIR__ );

$ashbpg_test_actions         = array();
$ashbpg_test_post_type       = null;
$ashbpg_test_taxonomy_links  = array();
$ashbpg_test_taxonomies      = array();
$ashbpg_test_meta            = array();
$ashbpg_test_post_type_exists = false;

function add_action( $hook, $callback, $priority = 10 ) {
    global $ashbpg_test_actions;
    $ashbpg_test_actions[] = array( $hook, $callback, $priority );
}

function __( $text ) {
    return $text;
}

function post_type_exists( $post_type ) {
    global $ashbpg_test_post_type_exists;
    return $ashbpg_test_post_type_exists && 'portfolio' === $post_type;
}

function register_post_type( $post_type, $args ) {
    global $ashbpg_test_post_type;
    $ashbpg_test_post_type = array( $post_type, $args );
}

function register_taxonomy_for_object_type( $taxonomy, $post_type ) {
    global $ashbpg_test_taxonomy_links;
    $ashbpg_test_taxonomy_links[] = array( $taxonomy, $post_type );
}

function taxonomy_exists() {
    return false;
}

function register_taxonomy( $taxonomy, $object_type, $args ) {
    global $ashbpg_test_taxonomies;
    $ashbpg_test_taxonomies[ $taxonomy ] = array( $object_type, $args );
}

function register_post_meta( $post_type, $key, $args ) {
    global $ashbpg_test_meta;
    $ashbpg_test_meta[ $key ] = array( $post_type, $args );
}

function current_user_can() {
    return true;
}

function add_filter() {}

require dirname( __DIR__ ) . '/includes/post-type.php';

ashbpg_register_portfolio_post_type();

if ( ! $ashbpg_test_post_type || 'portfolio' !== $ashbpg_test_post_type[0] ) {
    fwrite( STDERR, "Portfolio fallback was not registered.\n" );
    exit( 1 );
}

$args = $ashbpg_test_post_type[1];
if ( empty( $args['show_ui'] ) || empty( $args['show_in_rest'] ) || ! empty( $args['publicly_queryable'] ) ) {
    fwrite( STDERR, "Portfolio visibility contract is invalid.\n" );
    exit( 1 );
}

foreach ( array( 'title', 'thumbnail', 'page-attributes', 'revisions' ) as $support ) {
    if ( ! in_array( $support, $args['supports'], true ) ) {
        fwrite( STDERR, "Missing portfolio support: {$support}.\n" );
        exit( 1 );
    }
}

foreach ( array( 'subtitle', 'video_link', 'client', 'agency', 'director', 'editor', 'production_credit' ) as $meta_key ) {
    if ( empty( $ashbpg_test_meta[ $meta_key ][1]['show_in_rest'] ) ) {
        fwrite( STDERR, "Missing registered portfolio metadata: {$meta_key}.\n" );
        exit( 1 );
    }
}

foreach ( array( 'portfolio_collection', 'portfolio_artist' ) as $taxonomy ) {
    if ( empty( $ashbpg_test_taxonomies[ $taxonomy ][1]['show_ui'] ) || empty( $ashbpg_test_taxonomies[ $taxonomy ][1]['show_in_rest'] ) ) {
        fwrite( STDERR, "Missing managed taxonomy: {$taxonomy}.\n" );
        exit( 1 );
    }
    if ( ! empty( $ashbpg_test_taxonomies[ $taxonomy ][1]['publicly_queryable'] ) ) {
        fwrite( STDERR, "Managed taxonomy must not create public archive routes: {$taxonomy}.\n" );
        exit( 1 );
    }
}

$registered_before = $ashbpg_test_post_type;
$ashbpg_test_post_type_exists = true;
ashbpg_register_portfolio_post_type();
if ( $registered_before !== $ashbpg_test_post_type ) {
    fwrite( STDERR, "Existing portfolio registration was overwritten.\n" );
    exit( 1 );
}

echo "Portfolio content model contract passed.\n";
