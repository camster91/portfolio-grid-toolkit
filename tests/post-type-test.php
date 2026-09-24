<?php
/**
 * Minimal contract test for the plugin-owned portfolio content model.
 */

define( 'ABSPATH', __DIR__ );

$pgtk_test_actions         = array();
$pgtk_test_post_type       = null;
$pgtk_test_taxonomy_links  = array();
$pgtk_test_taxonomies      = array();
$pgtk_test_meta            = array();
$pgtk_test_post_type_exists = false;

function add_action( $hook, $callback, $priority = 10 ) {
    global $pgtk_test_actions;
    $pgtk_test_actions[] = array( $hook, $callback, $priority );
}

function __( $text ) {
    return $text;
}

function post_type_exists( $post_type ) {
    global $pgtk_test_post_type_exists;
    return $pgtk_test_post_type_exists && 'portfolio' === $post_type;
}

function register_post_type( $post_type, $args ) {
    global $pgtk_test_post_type;
    $pgtk_test_post_type = array( $post_type, $args );
}

function register_taxonomy_for_object_type( $taxonomy, $post_type ) {
    global $pgtk_test_taxonomy_links;
    $pgtk_test_taxonomy_links[] = array( $taxonomy, $post_type );
}

function taxonomy_exists() {
    return false;
}

function register_taxonomy( $taxonomy, $object_type, $args ) {
    global $pgtk_test_taxonomies;
    $pgtk_test_taxonomies[ $taxonomy ] = array( $object_type, $args );
}

function register_post_meta( $post_type, $key, $args ) {
    global $pgtk_test_meta;
    $pgtk_test_meta[ $key ] = array( $post_type, $args );
}

function current_user_can() {
    return true;
}

function add_filter() {}

require dirname( __DIR__ ) . '/includes/post-type.php';

pgtk_register_portfolio_post_type();

if ( ! $pgtk_test_post_type || 'portfolio' !== $pgtk_test_post_type[0] ) {
    fwrite( STDERR, "Portfolio fallback was not registered.\n" );
    exit( 1 );
}

$args = $pgtk_test_post_type[1];
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
    if ( empty( $pgtk_test_meta[ $meta_key ][1]['show_in_rest'] ) ) {
        fwrite( STDERR, "Missing registered portfolio metadata: {$meta_key}.\n" );
        exit( 1 );
    }
}

foreach ( array( 'portfolio_collection', 'portfolio_artist' ) as $taxonomy ) {
    if ( empty( $pgtk_test_taxonomies[ $taxonomy ][1]['show_ui'] ) || empty( $pgtk_test_taxonomies[ $taxonomy ][1]['show_in_rest'] ) ) {
        fwrite( STDERR, "Missing managed taxonomy: {$taxonomy}.\n" );
        exit( 1 );
    }
    if ( ! empty( $pgtk_test_taxonomies[ $taxonomy ][1]['publicly_queryable'] ) ) {
        fwrite( STDERR, "Managed taxonomy must not create public archive routes: {$taxonomy}.\n" );
        exit( 1 );
    }
}

$registered_before = $pgtk_test_post_type;
$pgtk_test_post_type_exists = true;
pgtk_register_portfolio_post_type();
if ( $registered_before !== $pgtk_test_post_type ) {
    fwrite( STDERR, "Existing portfolio registration was overwritten.\n" );
    exit( 1 );
}

echo "Portfolio content model contract passed.\n";
